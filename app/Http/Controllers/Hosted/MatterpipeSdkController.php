<?php

namespace App\Http\Controllers\Hosted;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class MatterpipeSdkController extends Controller
{
    public function __invoke(string $team, string $project): Response
    {
        $base = json_encode('/'.$project.'/__matterpipe', JSON_THROW_ON_ERROR);
        $javascript = <<<JS
(function () {
  const matterpipeBase = {$base};

  async function request(path, options) {
    const response = await fetch(matterpipeBase + path, {
      ...options,
      headers: {
        'Accept': 'application/json',
        ...(options && options.body instanceof FormData ? {} : {'Content-Type': 'application/json'}),
        ...(options && options.headers ? options.headers : {})
      }
    });

    if (!response.ok) {
      const error = new Error('Matterpipe request failed');
      error.status = response.status;
      error.body = await response.text();
      throw error;
    }

    return response.status === 204 ? null : response.json();
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
            return request(base, {method: 'POST', body: JSON.stringify({data: data})});
          },
          update: function (id, patch) {
            return request(base + '/' + encodeURIComponent(id), {method: 'PATCH', body: JSON.stringify({data: patch})});
          },
          delete: function (id) {
            return request(base + '/' + encodeURIComponent(id), {method: 'DELETE'});
          }
        };
      }
    },
    files: {
      upload: async function (file) {
        const form = new FormData();
        form.append('file', file);
        return request('/files', {method: 'POST', body: form});
      },
      getUrl: async function (fileId) {
        return matterpipeBase + '/files/' + encodeURIComponent(fileId);
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
