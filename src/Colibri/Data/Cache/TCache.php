<?php


/**
 * Data
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Cache
 */

namespace Colibri\Data\Cache;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\Events\EventsContainer;
use Colibri\Events\TEventDispatcher;
use Colibri\Utils\Cache\Mem;
use Colibri\Utils\Cache\Redis;

/**
 * Basic Event Dispatcher trait.
 */
trait TCache
{
    use TEventDispatcher;

    public function bootTCache()
    {
        $instance = $this;
        $this->HandleEvent(EventsContainer::Loading, function ($event, object &$args) use ($instance) {
            $args->return = $instance->getCached($args->type);
            if($args->return) {
                return false;
            }
            return true;
        });
        $this->HandleEvent(EventsContainer::Loaded, function ($event, $args) use ($instance) {
            $this->setCached($args->type, $args->data);
        });
        $this->HandleEvent(EventsContainer::Saved, function ($event, $args) use ($instance) {
            if($args->idf && $args->id) {
                $this->setCached($args->type, $args->data, $args->idf, $args->id);
                return;
            }
        });
        $this->HandleEvent(EventsContainer::Deleted, function ($event, $args) use ($instance) {
            if($args->idf && $args->id) {
                $this->setCached($args->type, null, $args->idf, $args->id);
                return;
            }
        });
    }

    public function getCached(mixed $key): mixed
    {
        $appDomainKey = App::$request->host;

        $readed = Mem::Read($appDomainKey . '_' . $key);
        if(!$readed) {
            return null;
        }
        // if(VariableHelper::isSerialized($readed)) {
        //     return VariableHelper::Unserialize($readed);
        // }
        return unserialize($readed);
    }

    public function setCached(string $key, mixed $data, ?string $idf = null, mixed $id = null): void
    {
        $appDomainKey = App::$request->host;
        if($id && $idf) {
            $ddata = $this->getCached($key) ?? [];
            foreach($ddata as $index => $item) {
                if($index == $id) {
                    if($data === null) {
                        array_splice($ddata, $index, 1);
                    } else {
                        $ddata[$index] = $data;
                    }
                    break;
                }
            }
            Mem::Write($appDomainKey . '_' . $key, serialize($ddata));
        } else {
            Mem::Write($appDomainKey . '_' . $key, serialize($data));
        }
    }

}
