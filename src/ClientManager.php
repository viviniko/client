<?php

namespace Viviniko\Client;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ClientManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * @var Client
     */
    protected $current;

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->session = $app['session'];
    }

    public function current()
    {
        if (!$this->current) {
            $request = $this->app['request'];
            $data = json_decode($request->cookie('ci'), true);
            $this->current = new Client($request, is_array($data) ? $data : []);
            $this->current->onDataChanged(function ($data) {
                Cookie::queue(Cookie::forever('ci', json_encode($data)));
            });
        }

        return $this->current;
    }

    public function id()
    {
        $id = $this->current()->data('id');
        if (!$id) {
            $id = strtolower( md5($this->current()->ipAddress() . microtime(true) . Str::random(128)));
            $this->current()->data(['id' => $id]);
        }

        return $id;
    }

    public function sign($key = 'sign')
    {
        $sign = $this->current()->get($key);
        if ($sign) {
            $this->current()->data([$key => $sign]);
        } else {
            $sign = $this->current()->data($key);
        }

        return $sign;
    }

    /**
     * Get entry referer.
     *
     * @return mixed|null|string
     */
    public function entryReferer()
    {
        if (!($referer = $this->session->get('client.entry-referer'))) {
            $referer = $this->current()->referer();
            $this->session->put(['client.entry-referer' => $referer]);
        }

        return $referer;
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
        return $this->current->{$method}(...$parameters);
    }
}