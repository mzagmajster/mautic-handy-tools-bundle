<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MZagmajsterHandyToolsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to send Slack notification about new mautic version of Mautic.
 */
class NotifyUpdatesCommand extends ContainerAwareCommand
{
    private function sendSlackMessage($message, $type)
    {
        $coreParametersHelper = $this->getContainer()->get('mautic.helper.core_parameters');

        //Options
        $slackUrl = $coreParametersHelper->get('mz_hdb_slack_hook');
        $channel  = $coreParametersHelper->get('mz_hdb_slack_channel');
        $siteUrl  = $coreParametersHelper->get('site_url');
        $botName  = 'Mautic Notifications';
        $icon     = ':mautic:';

        $attachments = [[
            'fallback' => '',
            'pretext'  => '',
            'color'    => '#ff6600',
            'fields'   => [
                [
                    'title' => 'Current Version',
                    'value' => MAUTIC_VERSION,
                    'short' => true,
                ],
                [
                    'title' => 'Site URL',
                    'value' => $siteUrl,
                    'short' => true,
                ],
            ],
        ]];

        $data = [
            'channel'     => $channel,
            'username'    => $botName,
            'text'        => strtoupper($type).': '.$message,
            'icon_emoji'  => $icon,
            'attachments' => $attachments,
        ];

        $dataString = json_encode($data);

        $ch = curl_init($slackUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: '.strlen($dataString), ]
            );

        //Execute CURL
        $result = curl_exec($ch);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mz:update:notify')
            ->setDescription('Send notification to Slack')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command sends Slack notification if newer version of Mautic is available.

<info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));

        $updateHelper = $this->getContainer()->get('mautic.helper.update');
        $updateData   = $updateHelper->fetchData(true);

        if ($updateData['error']) {
            $this->sendSlackMessage($translator->trans($updateData['message'], 'error'));
            $output->writeln('<error>'.$translator->trans($updateData['message']).'</error>');
        } elseif ('mautic.core.updater.running.latest.version' == $updateData['message']) {
            $output->writeln('<info>'.$translator->trans($updateData['message']).'</info>');
        } else {
            $message = $translator->trans($updateData['message'], ['%version%' => $updateData['version'], '%announcement%' => $updateData['announcement']]);
            $this->sendSlackMessage($message, 'info');
            $output->writeln($message);
        }

        return 0;
    }
}
