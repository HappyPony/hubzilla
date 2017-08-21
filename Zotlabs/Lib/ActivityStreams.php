<?php

namespace Zotlabs\Lib;

class ActivityStreams {

	public $data;
	public $valid  = false;
	public $id     = '';
	public $type   = '';
	public $actor  = null;
	public $obj    = null;
	public $tgt    = null;
	public $origin = null;
	public $owner  = null;

	function __construct($string) {

		$this->data = json_decode($string,true);
		if($this->data) {
			$this->valid = true;
		}

		if($this->is_valid()) {
			$this->id     = $this->get_property_obj('id');
			$this->type   = $this->get_primary_type();
			$this->actor  = $this->get_compound_property('actor');
			$this->obj    = $this->get_compound_property('object');
			$this->tgt    = $this->get_compound_property('target');
			$this->origin = $this->get_compound_property('origin');
			$this->owner  = $this->get_compound_property('owner','','http://purl.org/zot/protocol');

			if(($this->type === 'Note') && (! $this->obj)) {
				$this->obj = $this->data;
				$this->type = 'Create';
			}
		}
	}

	function is_valid() {
		return $this->valid;
	}

	function get_namespace($base,$namespace) {

		$key = null;

		foreach( [ $this->data, $base ] as $b ) {
			if(! $b)
				continue;
			if(array_key_exists('@context',$b)) {
				if(is_array($b['@context'])) {
					foreach($b['@context'] as $ns) {
						if(is_array($ns)) {
							foreach($ns as $k => $v) {
								if($namespace === $v)
									$key = $k;
							}
						}
						else {
							if($namespace === $ns) {
								$key = '';
							}
						}
					}
				}
				else {
					if($namespace === $b['@context']) {
						$key = '';
					}
				}
			}
		}
		return $key;
	}


	function get_property_obj($property,$base = '',$namespace = 'https://www.w3.org/ns/activitystreams') {
		$prefix = $this->get_namespace($base,$namespace);
		if($prefix === null)
			return null;	
		$base = (($base) ? $base : $this->data);
		$propname = (($prefix) ? $prefix . ':' : '') . $property;
		return ((array_key_exists($propname,$base)) ? $base[$propname] : null);
	}

	function fetch_property($url) {
		$redirects = 0;
		if(! check_siteallowed($url)) {
			logger('blacklisted: ' . $url);
			return null;
		}

		$x = z_fetch_url($url,true,$redirects,
			['headers' => [ 'Accept: application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"']]);
		if($x['success'])
			return json_decode($x['body'],true);
		return null;
	}

	function get_compound_property($property,$base = '',$namespace = 'https://www.w3.org/ns/activitystreams') {
		$x = $this->get_property_obj($property,$base,$namespace);
		if($this->is_url($x)) {
			$x = $this->fetch_property($x); 	
		}
		return $x;
	}

	function is_url($url) {
		if(($url) && (! is_array($url)) && (strpos($url,'http') === 0)) {
			return true;
		}
		return false;
	}

	function get_primary_type($base = '',$namespace = 'https://www.w3.org/ns/activitystreams') {
		if(! $base)
			$base = $this->data;
		$x = $this->get_property_obj('type',$base,$namespace);
		if(is_array($x)) {
			foreach($x as $y) {
				if(strpos($y,':') === false) {
					return $y;
				}
			}
		}
		return $x;
	}

	function debug() {
		$x = var_export($this,true);
		return $x;
	}

}