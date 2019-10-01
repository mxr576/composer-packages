<?php

declare(strict_types=1);

namespace drupol\ComposerPackages\Exporter;

use Composer\Script\Event;
use drupol\ComposerPackages\Twig\CamelCaseExtension;
use drupol\ComposerPackages\Twig\VarExportExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class Exporter.
 */
abstract class Exporter implements ExporterInterface
{
    /**
     * @var \Composer\Script\Event
     */
    private $event;

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * Exporter constructor.
     *
     * @param \Composer\Script\Event $event
     */
    public function __construct(Event $event)
    {
        $this->twig = new Environment(
            new FilesystemLoader(\dirname(__DIR__) . '/templates')
        );

        $this->twig->addExtension(new CamelCaseExtension());
        $this->twig->addExtension(new VarExportExtension());

        $this->event = $event;
    }

    /**
     * @param string $template
     * @param string $destination
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function exportToFile(string $template, string $destination): void
    {
        $data = $this->exportToArray() + [
            'generatedAt' => \time(),
            'rootPackageName' => $this->getEvent()->getComposer()->getPackage()->getName(),
        ];

        $installPathTmp = \sprintf(
            '%s_%s',
            $destination,
            \uniqid('tmp', true)
        );

        \file_put_contents(
            $installPathTmp,
            $this->twig->render(
                $template,
                $data
            )
        );
        \chmod($installPathTmp, 0664);
        \rename($installPathTmp, $destination);
    }

    /**
     * @return \Composer\Script\Event
     */
    protected function getEvent(): Event
    {
        return $this->event;
    }
}
