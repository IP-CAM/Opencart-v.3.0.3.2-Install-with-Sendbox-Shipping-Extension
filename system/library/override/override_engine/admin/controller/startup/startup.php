<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart class override_engine_ControllerStartupStartup                           */
/*                                                                                    */
/*  Copyright © 2017 by J.Neuhoff (www.mhccorp.com)                                   */
/*                                                                                    */
/*  This file is part of the Override Engine for OpenCart.                            */
/*                                                                                    */
/*  OpenCart is free software: you can redistribute it and/or modify                  */
/*  it under the terms of the GNU General Public License as published by              */
/*  the Free Software Foundation, either version 3 of the License, or                 */
/*  (at your option) any later version.                                               */
/*                                                                                    */
/*  OpenCart is distributed in the hope that it will be useful,                       */
/*  but WITHOUT ANY WARRANTY; without even the implied warranty of                    */
/*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                     */
/*  GNU General Public License for more details.                                      */
/*                                                                                    */
/*  You should have received a copy of the GNU General Public License                 */
/*  along with OpenCart.  If not, see <http://www.gnu.org/licenses/>.                 */
/* ---------------------------------------------------------------------------------- */

class override_engine_ControllerStartupStartup extends ControllerStartupStartup {

	public function index() {
		$this->start();
		parent::index();
	}
	
	protected function start() {
		// repeat loading of some framework settings from 'system/framework.php', 
		// this time using the override engine for class instantiations

		// Log
		$log = $this->factory->newLog($this->config->get('error_filename'));
		$this->registry->set('log', $log);

		// Request
		$this->registry->set('request', $this->factory->newRequest());

		// Cache
		$this->registry->set('cache', $this->factory->newCache($this->config->get('cache_engine'), $this->config->get('cache_expire')));

		// Url
		if ($this->config->get('url_autostart')) {
			$this->registry->set('url', $this->factory->newUrl($this->config->get('site_url'), $this->config->get('site_ssl')));
		}

		// Document
		$this->registry->set('document', $this->factory->newDocument());
	}
}
?>