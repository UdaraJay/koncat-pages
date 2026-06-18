import { InfoIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import type { ProjectShareContext } from '@/types';

type Props = {
    share: ProjectShareContext;
    action: 'Log in' | 'Register';
};

export default function ProjectShareAlert({ share, action }: Props) {
    return (
        <Alert
            data-test="project-share-alert"
            className="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-100 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400"
        >
            <InfoIcon />
            <AlertDescription className="text-blue-900 dark:text-blue-100">
                {action} to open "{share.projectName}", shared by{' '}
                {share.sharerName}.
            </AlertDescription>
        </Alert>
    );
}
