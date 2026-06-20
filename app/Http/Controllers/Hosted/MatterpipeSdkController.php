<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class MatterpipeSdkController extends Controller
{
    public function __invoke(string $team, string $project): Response
    {
        $fileBase = json_encode(sprintf(
            '%s://%s.%s/%s/__matterpipe',
            config('matterpipe.hosting_scheme'),
            $team,
            config('matterpipe.hosting_domain'),
            $project,
        ), JSON_THROW_ON_ERROR);
        $parentOrigin = json_encode(sprintf(
            '%s://%s.%s',
            config('matterpipe.hosting_scheme'),
            $team,
            config('matterpipe.hosting_domain'),
        ), JSON_THROW_ON_ERROR);
        $javascript = <<<JS
(function () {
  const matterpipeFileBase = {$fileBase};
  const parentOrigin = {$parentOrigin};
  let nextId = 1;
  const pending = new Map();

  window.addEventListener('message', function (event) {
    if (event.origin !== parentOrigin || !event.data || event.data.type !== 'matterpipe:response') {
      return;
    }

    const entry = pending.get(event.data.id);

    if (!entry) {
      return;
    }

    pending.delete(event.data.id);

    if (!event.data.ok) {
      const error = new Error('Matterpipe request failed');
      error.status = event.data.status;
      error.body = event.data.body || '';
      entry.reject(error);
      return;
    }

    if (event.data.status === 204 || event.data.body === '') {
      entry.resolve(null);
      return;
    }

    try {
      entry.resolve(JSON.parse(event.data.body));
    } catch (error) {
      entry.reject(error);
    }
  });

  function request(path, options) {
    return new Promise(function (resolve, reject) {
      const id = nextId++;
      pending.set(id, {resolve: resolve, reject: reject});

      window.parent.postMessage({
        type: 'matterpipe:request',
        id: id,
        method: options && options.method ? options.method : 'GET',
        path: path,
        json: options && Object.prototype.hasOwnProperty.call(options, 'json') ? options.json : undefined,
        file: options && options.file ? options.file : undefined
      }, parentOrigin);

      setTimeout(function () {
        if (!pending.has(id)) {
          return;
        }

        pending.delete(id);
        reject(new Error('Matterpipe request timed out'));
      }, 30000);
    });
  }

  window.matterpipe = {
    identity: {
      get: function () {
        return request('/identity');
      }
    },
    db: {
      collection: function (name) {
        const base = '/db/' + encodeURIComponent(name);

        return {
          list: function (params) {
            const search = new URLSearchParams(params || {});
            return request(base + (search.toString() ? '?' + search.toString() : ''));
          },
          get: function (id) {
            return request(base + '/' + encodeURIComponent(id));
          },
          create: function (data) {
            return request(base, {method: 'POST', json: {data: data}});
          },
          update: function (id, patch) {
            return request(base + '/' + encodeURIComponent(id), {method: 'PATCH', json: {data: patch}});
          },
          delete: function (id) {
            return request(base + '/' + encodeURIComponent(id), {method: 'DELETE'});
          }
        };
      }
    },
    files: {
      upload: async function (file) {
        return request('/files', {method: 'POST', file: file});
      },
      getUrl: async function (fileId) {
        return matterpipeFileBase + '/files/' + encodeURIComponent(fileId);
      },
      delete: function (fileId) {
        return request('/files/' + encodeURIComponent(fileId), {method: 'DELETE'});
      }
    }
  };
})();
JS;

        return response($javascript, 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
    }
}
