<?php

namespace Icinga\Module\Businessprocess\ProvidedHook;

use Icinga\Application\Config;
use Icinga\Application\Hook\GrapherHook;
use Icinga\Module\Businessprocess\Renderer\TileRenderer;
use Icinga\Module\Businessprocess\Renderer\TreeRenderer;
use Icinga\Module\Businessprocess\State\MonitoringState;
use Icinga\Module\Businessprocess\Storage\LegacyStorage;
use Icinga\Module\Businessprocess\Web\Url;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;

class Grapher extends GrapherHook
{

    private $storage;

    public function init()
    {
        try {
            $this->storage = new LegacyStorage(
                Config::module('businessprocess')->getSection('global')
            );
        } catch (Exception $e) {
            // Ignore and don't display anything
        }

        $this->hasPreviews = true;
    }

    public function has(MonitoredObject $object)
    {
        return $object instanceof Service && $object->check_command == 'icingacli-businessprocess';
    }


    public function getPreviewHtml(MonitoredObject $object)
    {
        if (!$this->has($object) || !$this->storage) {
            return '';
        }

        $bpName = $object->_service_icingacli_businessprocess_process;
        $bp = $this->storage->loadProcess($bpName);

        MonitoringState::apply($bp);

        if (filter_var($object->_service_icingacli_businessprocess_grapher_tree, FILTER_VALIDATE_BOOLEAN)) {
            $renderer = new TreeRenderer($bp);
        } else {
            $renderer = new TileRenderer($bp);
        }

        $renderer->setBaseUrl(Url::fromPath('businessprocess/process/show?config=' . $bpName . '&node=' . $bpName));

        $html = '<div class="icinga-module module-businessprocess"><h2>Business Process</h2>';
        $html = $html . $renderer->render() . '</div>';
        return $html
    }
}

