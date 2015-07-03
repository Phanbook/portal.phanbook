<?php

use Phalcon\Mvc\Controller,
	Phalcon\Mvc\Dispatcher;

class ControllerBase extends Controller
{
	public function requestInitialize()
	{
		if ($this->config->application->debug) {
            $cdnUrl = $this->config->application->baseUri;
		} else {
            $cdnUrl = $this->config->application->cdn;
		}

		$baseUrl = $this->config->application->baseUri;

		/**
		 * Docs path and CDN url
		 */
		$lang = $this->getLang();

		/**
		 * Find the languages available
		 */
		$languages           = $this->config->languages;
		$languagesAvailable  = '';
		$selected            = '';
		$url                 = $this->request->getScheme() . '://'
							 . $this->request->getHttpHost()
							 . $this->config->application->baseUri;
		$uri                 = $this->router->getRewriteUri();
		foreach ($languages as $key => $value) {
			$selected = ($key == $lang) ? " selected='selected'" : '';
			$href     = $url .  str_replace("/{$lang}", "{$key}", $uri);
			$languagesAvailable .= "<a role='menuitem' tabindex='-1' href='{$href}' class='flag-{$key}'>{$value}</a>";

			#$languagesAvailable .= "<option value='{$href}'{$selected}>{$value}</option>"; // old way to do it
		}

		$this->view->setVar('language', $lang);
		$this->view->setVar('baseurl', $baseUrl);
		$this->view->setVar('languages_available', $languagesAvailable);
		$this->view->setVar('docs_root', 'http://docs.phalconphp.com/'.$lang.'/latest/');
		$this->view->setVar('cdn_url', $cdnUrl);
        $this->view->setVar('isFrontpage', true);
	}

	/**
	 * @param Dispatcher $dispatcher
	 *
	 * @return bool
	 */
	public function beforeExecuteRoute(Dispatcher $dispatcher)
	{
		if (!$this->config->application->debug) {

			$lang = $this->getLang();

			$key = preg_replace(
				'/[^a-zA-Z0-9\_]/',
				'',
				$lang . '-' . $dispatcher->getControllerName() . '-' . $dispatcher->getActionName() . '-' . implode('-' , $dispatcher->getParams())
			);
			$this->view->cache(array('key' => $key));
			if ($this->view->getCache()->exists($key)) {
				return false;
			}
		}


		$this->requestInitialize();
		return true;
	}

	protected function getUriParameter($parameter)
	{
		return $this->dispatcher->getParam($parameter);
	}

    protected function getLang()
    {
        $lang = $this->getUriParameter('language');

        if (!$lang) {
            $languagesAvailable = array_keys($this->config->languages->toArray());

            foreach ($this->request->getLanguages() as $httpLang) {
                $httpLang = mb_strtolower(substr($httpLang['language'], 0, 2));
                if (in_array($httpLang, $languagesAvailable)) {
                    return $httpLang;
                }
            }
        } else {
            return $lang;
        }

        return 'en';
    }
}
