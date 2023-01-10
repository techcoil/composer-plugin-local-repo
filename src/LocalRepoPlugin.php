<?php

namespace Techcoil\LocalRepo;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\PathRepository;

class LocalRepoPlugin implements PluginInterface, EventSubscriberInterface
{

    const EXTRA_CONFIG_KEY = 'local-repo';

    const DEBUG_TAG = 'techcoil/composer-plugin-local-repo';

    /** @var Composer|null */
    protected $composer;

    /** @var array|null */
    protected $config;

    /** @var IOInterface|null */
    private $io;

    /**
     * Return the composer instance.
     */
    public function getComposer(): ?Composer
    {
        return $this->composer;
    }

    public static function getSubscribedEvents()
    {
        return [
            'init' => [
                ['addLocalRepos', 0],
            ],
        ];
    }

    /**
     * @param Event $event
     * @return void
     */
    public function addLocalRepos(Event $event)
    {
        $possible_packages = $this->locateComposerJsons();
        foreach ($possible_packages as $package_config) {
            $config = json_decode(file_get_contents($package_config), true);
            if (is_array($config)) {
                $package_name = $config['name'] ?? '';
                $this->debug("Adding local repo {$package_name} from: {$package_config}");
                $this->composer->getRepositoryManager()->addRepository(new PathRepository([
                    'url' => dirname($package_config),
                    'options' => [
                        'symlink' => $this->getConfig('symlink', false),
                    ],
                ], $this->io, $this->composer->getConfig()));
            }
        }
    }

    protected function locateComposerJsons()
    {
        $paths = (array)$this->getConfig('paths', []);
        $depth = (int)$this->getConfig('depth', 1);
        $ignore = (array)$this->getConfig('ignore', []);

        $possible_packages = [];
        foreach ($paths as $path) {
            $possible_packages = array_merge($possible_packages, $this->findComposerJsons($path, $depth, $ignore));
        }

        return $possible_packages;
    }

    protected function isPathIgnored($path, $ignores = [])
    {
        foreach ($ignores as $ignore) {
            if (fnmatch($ignore, $path) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function findComposerJsons($path, $depth, $ignore)
    {
        $paths = [];
        for ($i = 0; $i <= $depth; $i++) {
            $depth_wildcard = str_repeat('/*', $i);
            $configs = glob("{$path}{$depth_wildcard}/composer.json");

            foreach ($configs as $config) {
                $dir = dirname($config);
                if ($this->isPathIgnored($dir, $ignore) === false) {
                    $paths[] = $config;
                } else {
                    $this->debug("path `{$dir}` is ignored by config");
                }
            }
        }
        return $paths;
    }

    /**
     * Return the config value for the given key.
     *
     * Returns the entire root config array, if key is set to null.
     *
     * @return mixed
     */
    public function getConfig(?string $key)
    {
        if ($this->config === null) {
            $this->config = [
                'path' => 'src',
                'depth' => 1,
                'symlink' => true,
                'ignore' => []
            ];

            $rootPackage = $this->getComposer()->getPackage();
            $extra = $rootPackage->getExtra();
            $config = $extra[self::EXTRA_CONFIG_KEY] ?? [];
            $this->config = array_merge($this->config, $config);
        }

        return $key !== null ? ($this->config[$key] ?? null) : $this->config;
    }


    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $this->composer = null;
        $this->io = null;
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    protected function debug($message, $context = [])
    {
        $this->io->debug(self::DEBUG_TAG . ' :: ' . $message, $context);
    }
}
