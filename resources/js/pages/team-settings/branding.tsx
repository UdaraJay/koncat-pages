import { Form, Head } from '@inertiajs/react';
import { Image, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { branding } from '@/routes/team-settings';
import { update as updateBranding } from '@/routes/team-settings/branding';
import type { Team, TeamPermissions } from '@/types';

type Props = {
    team: Team;
    permissions: TeamPermissions;
};

export default function TeamSettingsBranding({ team, permissions }: Props) {
    const [backgroundColor, setBackgroundColor] = useState(
        team.brandBackgroundColor ?? '',
    );
    const [foregroundColor, setForegroundColor] = useState(
        team.brandForegroundColor ?? '',
    );
    const [logoPreviewUrl, setLogoPreviewUrl] = useState<string | null>(
        team.brandLogoUrl ?? null,
    );
    const [removeLogo, setRemoveLogo] = useState(false);

    useEffect(() => {
        return () => {
            if (logoPreviewUrl?.startsWith('blob:')) {
                URL.revokeObjectURL(logoPreviewUrl);
            }
        };
    }, [logoPreviewUrl]);

    const previewLogoUrl = removeLogo ? null : logoPreviewUrl;
    const previewBackgroundColor = backgroundColor || '#f7f7f4';
    const previewForegroundColor = foregroundColor || '#181816';
    const previewMutedColor = foregroundColor || '#686861';

    const pageTitle = useMemo(
        () =>
            permissions.canUpdateTeam
                ? `Edit ${team.name} branding`
                : `View ${team.name} branding`,
        [permissions.canUpdateTeam, team.name],
    );

    const updateLogoPreview = (file?: File) => {
        setRemoveLogo(false);

        if (logoPreviewUrl?.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreviewUrl);
        }

        setLogoPreviewUrl(
            file ? URL.createObjectURL(file) : (team.brandLogoUrl ?? null),
        );
    };

    return (
        <>
            <Head title={`${team.name} branding`} />

            <h1 className="sr-only">{pageTitle}</h1>

            <div className="flex flex-col space-y-10">
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Branding"
                        description={
                            permissions.canUpdateTeam
                                ? 'Manage the team logo and hosted frame colors'
                                : ''
                        }
                    />

                    <div
                        className="overflow-hidden rounded-lg border"
                        style={{
                            backgroundColor: previewBackgroundColor,
                            color: previewForegroundColor,
                        }}
                    >
                        <div className="flex h-10 items-center justify-between gap-4 px-3">
                            <div className="flex min-w-0 items-center gap-2">
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded border border-current/20 bg-white/70">
                                    {previewLogoUrl ? (
                                        <img
                                            src={previewLogoUrl}
                                            alt=""
                                            className="h-full w-full object-contain"
                                        />
                                    ) : (
                                        <Image className="h-3.5 w-3.5 opacity-60" />
                                    )}
                                </span>
                                <span className="truncate text-sm font-medium">
                                    Project title
                                </span>
                            </div>
                            <span
                                className="shrink-0 text-xs"
                                style={{ color: previewMutedColor }}
                            >
                                User
                            </span>
                        </div>
                        <div className="mx-1.5 h-16 rounded-t border border-b-0 bg-background" />
                    </div>

                    {permissions.canUpdateTeam ? (
                        <Form
                            action={updateBranding.url(team.slug)}
                            method="post"
                            encType="multipart/form-data"
                            className="space-y-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="_method"
                                        value="PATCH"
                                    />
                                    <input
                                        type="hidden"
                                        name="remove_logo"
                                        value={removeLogo ? '1' : '0'}
                                    />

                                    <div className="grid gap-2">
                                        <Label htmlFor="logo">Team logo</Label>
                                        <div className="flex items-center gap-3">
                                            <Input
                                                id="logo"
                                                name="logo"
                                                type="file"
                                                accept="image/*"
                                                data-test="team-brand-logo-input"
                                                onChange={(event) =>
                                                    updateLogoPreview(
                                                        event.currentTarget
                                                            .files?.[0],
                                                    )
                                                }
                                            />
                                            {team.brandLogoUrl ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    aria-label="Remove logo"
                                                    data-test="team-brand-logo-remove"
                                                    onClick={() =>
                                                        setRemoveLogo(true)
                                                    }
                                                >
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            ) : null}
                                        </div>
                                        <InputError message={errors.logo} />
                                    </div>

                                    {team.brandLogoUrl ? (
                                        <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Checkbox
                                                checked={removeLogo}
                                                onCheckedChange={(checked) =>
                                                    setRemoveLogo(
                                                        checked === true,
                                                    )
                                                }
                                                aria-label="Remove current logo"
                                            />
                                            Remove current logo
                                        </label>
                                    ) : null}

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="brand_background_color">
                                                Background color
                                            </Label>
                                            <input
                                                type="hidden"
                                                name="brand_background_color"
                                                value={backgroundColor}
                                            />
                                            <div className="flex gap-2">
                                                <Input
                                                    id="brand_background_color"
                                                    type="color"
                                                    data-test="team-brand-background-input"
                                                    value={
                                                        backgroundColor ||
                                                        '#f7f7f4'
                                                    }
                                                    onChange={(event) =>
                                                        setBackgroundColor(
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setBackgroundColor('')
                                                    }
                                                >
                                                    Clear
                                                </Button>
                                            </div>
                                            <InputError
                                                message={
                                                    errors.brand_background_color
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="brand_foreground_color">
                                                Foreground color
                                            </Label>
                                            <input
                                                type="hidden"
                                                name="brand_foreground_color"
                                                value={foregroundColor}
                                            />
                                            <div className="flex gap-2">
                                                <Input
                                                    id="brand_foreground_color"
                                                    type="color"
                                                    data-test="team-brand-foreground-input"
                                                    value={
                                                        foregroundColor ||
                                                        '#181816'
                                                    }
                                                    onChange={(event) =>
                                                        setForegroundColor(
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setForegroundColor('')
                                                    }
                                                >
                                                    Clear
                                                </Button>
                                            </div>
                                            <InputError
                                                message={
                                                    errors.brand_foreground_color
                                                }
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        type="submit"
                                        data-test="team-brand-save-button"
                                        disabled={processing}
                                    >
                                        Save
                                    </Button>
                                </>
                            )}
                        </Form>
                    ) : null}
                </div>
            </div>
        </>
    );
}

TeamSettingsBranding.layout = (props: {
    team: { name: string; slug: string };
}) => ({
    breadcrumbs: [
        {
            title: 'Team settings',
            href: `/${props.team.slug}/settings/general`,
        },
        {
            title: 'Branding',
            href: branding.url(props.team.slug),
        },
    ],
});
