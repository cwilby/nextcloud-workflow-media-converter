<?php

namespace OCA\WorkflowMediaConverter\Settings;

use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection
{
    private IL10N $l;
    private IURLGenerator $urlGenerator;

    public function __construct(IL10N $l, IURLGenerator $urlGenerator)
    {
        $this->l = $l;
        $this->urlGenerator = $urlGenerator;
    }

    public function getID(): string
    {
        return Application::APP_ID;
    }

    public function getName(): string
    {
        return $this->l->t('Media conversion');
    }

    public function getPriority()
    {
        return 80;
    }

    public function getIcon()
    {
        return $this->urlGenerator->imagePath(Application::APP_ID, 'icon.svg');
    }
}
