<?php

namespace Viviniko\Client;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class Client
{
    protected $request;

    protected $agent;

    protected $data = null;

    protected $onDataChanged = null;

    public function __construct(Request $request, $data = null)
    {
        $this->request = $request;
        $this->agent = new Agent($this->request->server->all());
        $this->data = $data;
    }

    public function data($key = null, $default = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
            if ($this->onDataChanged) {
                call_user_func($this->onDataChanged, $this->data);
            }
            return $this;
        }

        if (is_string($key)) {
            return data_get($this->data, $key, $default);
        }

        return $this->data;
    }

    public function onDataChanged($onDataChanged)
    {
        $this->onDataChanged = $onDataChanged;
        return $this;
    }

    public function get($key, $default = null)
    {
        return $this->request->get($key, $default);
    }

    public function ipAddress()
    {
        return $this->request->ip();
    }

    public function location()
    {
        return geoip()->getLocation($this->ipAddress());
    }

    public function referer()
    {
        return $this->request->header('referer');
    }

    /**
     * Dynamically call.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->agent->{$method}(...$parameters);
    }
}
