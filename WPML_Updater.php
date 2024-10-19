<?php

namespace WPML_Updater;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreCommandRunEvent;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\Repository\PackageRepository;
use Composer\Script\ScriptEvents;
use Throwable;

class WPML_Updater implements PluginInterface, EventSubscriberInterface
{
    protected Composer $composer;

    protected IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $existingConfig = $this->composer->getConfig()->get('get-parameter')['wpml.org'] ?? [];
        if (empty($existingConfig)) {
            $this->composer->getConfig()
                ->getLocalAuthConfigSource()?->addConfigSetting('get-parameter.wpml.org', [
                    "user_id" => "",
                    "subscription_key" => "",
                ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PRE_COMMAND_RUN => ['registerRepositories', 0],
            ScriptEvents::PRE_UPDATE_CMD => ['registerRepositories', 0],
            ScriptEvents::PRE_INSTALL_CMD => ['registerRepositories', 0],
            PluginEvents::PRE_FILE_DOWNLOAD => ['handlePreDownloadEvent', -1]
        ];
    }

    public function registerRepositories(PreCommandRunEvent|\Composer\Script\Event $event): void
    {
        static $packages;
        if ($event instanceof PreCommandRunEvent && !in_array($event->getCommand(), ['update', 'install', 'require'])) {
            return;
        }
        try {
            if (!isset($packages)) {
                $remoteJson = json_decode(file_get_contents('https://d2salfytceyqoe.cloudfront.net/wpml33-products.json'), true);

                $packages = [];
                foreach ($remoteJson['downloads']['plugins'] as $package) {
                    if ($package['free-on-wporg'] || $package['fallback-free-on-wporg']) {
                        continue;
                    }
                    $packages[] = [
                        'package' => [
                            'name' => 'wpml/' . $package['slug'],
                            'type' => 'wordpress-plugin',
                            'description' => $package['description'],
                            'version' => $package['version'],
                            'require' => !empty($package['glue_check_slug']) ? [
                                'wpackagist-plugin/' . $package['glue_check_slug'] => '*',
                                //'roots/wordpress' => '^' . $package->tested
                            ] : [],
                            "dist" => [
                                "type" => "zip",
                                "url" => $package['url'],
                            ],
                        ]
                    ];
                }
            }

            foreach ($packages as $packageConfig) {
                ($event instanceof \Composer\Script\Event ? $event->getComposer() : $this->composer)
                    ->getRepositoryManager()
                    ->addRepository(new PackageRepository($packageConfig));
            }
        } catch (Throwable) {
            $this->io->error('Could not load WPML plugin information.');
        }
    }

    /**
     * Fulfill package URL placeholders before downloading the package.
     */
    public function handlePreDownloadEvent(PreFileDownloadEvent $event): void
    {
        static $warned = false;

        $url = $event->getProcessedUrl();
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;
        $query = $parsed['query'] ?? null;

        $auth = $this->composer->getConfig()->get('get-parameter');
        if (!$warned && empty($auth['wpml.org']) && $host === 'wpml.org' && !str_contains($query, 'subscription_key')) {
            $this->io->warning('Missing authentication parameters for wpml.org, you should set in your auth.json
{
  "get-parameter": {
    "wpml.org": {
      "user_id": "YOUR_USER_ID",
      "subscription_key": "YOUR_SUBSCRIPTION_KEY"
    }
  }
}
');
        } else if (!$warned) {
            foreach ($auth as $authHost => $parameters) {
                if ($host === $authHost) {
                    $event->setProcessedUrl(
                        $url . ($query ? '&' : '?') . http_build_query($parameters)
                    );
                }
            }
        }
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }


    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }
}
