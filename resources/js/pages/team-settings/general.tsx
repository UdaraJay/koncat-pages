import { Form, Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DeleteTeamModal from '@/components/delete-team-modal';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { general, update } from '@/routes/team-settings';
import type { Team, TeamPermissions } from '@/types';

type Props = {
    team: Team;
    permissions: TeamPermissions;
};

export default function TeamSettingsGeneral({ team, permissions }: Props) {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [subdomain, setSubdomain] = useState(team.subdomain);

    const pageTitle = useMemo(
        () =>
            permissions.canUpdateTeam
                ? `Edit ${team.name}`
                : `View ${team.name}`,
        [permissions.canUpdateTeam, team.name],
    );

    return (
        <>
            <Head title={`${team.name} settings`} />

            <h1 className="sr-only">{pageTitle}</h1>

            <div className="flex flex-col space-y-10">
                <div className="space-y-6">
                    {permissions.canUpdateTeam ? (
                        <>
                            <Heading
                                variant="small"
                                title="General"
                                description="Update your team name and publishing settings"
                            />

                            <Form
                                action={update.url(team.slug)}
                                method="patch"
                                className="space-y-6"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Team name
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                data-test="team-name-input"
                                                defaultValue={team.name}
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="subdomain">
                                                Publishing subdomain
                                            </Label>
                                            <Input
                                                id="subdomain"
                                                name="subdomain"
                                                data-test="team-subdomain-input"
                                                defaultValue={team.subdomain}
                                                onChange={(event) =>
                                                    setSubdomain(
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                {team.hostingScheme}://
                                                {subdomain || team.subdomain}.
                                                {team.hostingDomain}
                                            </p>
                                            <InputError
                                                message={errors.subdomain}
                                            />
                                        </div>

                                        <div className="flex items-center gap-4">
                                            <Button
                                                type="submit"
                                                data-test="team-save-button"
                                                disabled={processing}
                                            >
                                                Save
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </>
                    ) : (
                        <Heading variant="small" title={team.name} />
                    )}
                </div>

                {permissions.canDeleteTeam && !team.isPersonal ? (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Delete team"
                            description="Permanently delete your team"
                        />
                        <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                            <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                <p className="font-medium">Warning</p>
                                <p className="text-sm">
                                    Please proceed with caution, this cannot be
                                    undone.
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                data-test="delete-team-button"
                                onClick={() => setDeleteDialogOpen(true)}
                            >
                                Delete team
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>

            {permissions.canDeleteTeam && !team.isPersonal ? (
                <DeleteTeamModal
                    team={team}
                    open={deleteDialogOpen}
                    onOpenChange={setDeleteDialogOpen}
                />
            ) : null}
        </>
    );
}

TeamSettingsGeneral.layout = (props: { team: { name: string; slug: string } }) => ({
    breadcrumbs: [
        {
            title: 'Team settings',
            href: general(props.team.slug),
        },
        {
            title: 'General',
            href: general(props.team.slug),
        },
    ],
});
