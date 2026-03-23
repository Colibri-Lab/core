<?php


/**
 * Data
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Cache
 */

namespace Colibri\Data\Cache;

use Colibri\Common\VariableHelper;
use Colibri\Events\EventsContainer;
use Colibri\Events\TEventDispatcher;
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
                app_debug('Cache found for type: ' . $args->type);
                return false;
            }
            app_debug('Cache NOT found for type: ' . $args->type);
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

    public function cacheKey(string $type, mixed $data): string
    {
        return (string)$type . ':' . (\is_scalar($data) ? $data : md5(VariableHelper::Serialize($data)));
    }

    public function getCached(mixed $key): mixed
    {
        $readed = Redis::Read($key);
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
        if($id && $idf) {
            $ddata = $this->getCached($key) ?? [];
            for($i = 0; $i < count($ddata); $i++) {
                if($ddata[$i]->$idf == $id) {
                    if($data === null) {
                        array_splice($ddata, $i, 1);
                    } else {
                        $ddata[$i] = $data;
                    }
                    break;
                }
            }
            Redis::Write($key, serialize($ddata));
        } else {
            Redis::Write($key, serialize($data));
        }
    }

}
