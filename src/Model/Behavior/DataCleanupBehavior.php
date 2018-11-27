<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;

/**
 * DataCleanup behavior
 *
 * Data cleanup operations on object creations to allow operations with `dirty` input data
 * and setting basic values per fields, per object type (from configuration 'DefaultValues')
 *
 * This Behavoir acts only on Model.beforeMarshal event
 *
 * @since 4.0.0
 */
class DataCleanupBehavior extends Behavior
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'fields' => [
            'status' => 'draft',
            'deleted' => 0,
        ]
    ];

    /**
     * Substitute NULL input data with defaults when a new Entity is being created
     * to avoid errors
     *
     * @param \Cake\Event\Event $event The event dispatched
     * @param \ArrayObject $data The input data to save
     * @return void
     */
    public function beforeMarshal(Event $event, \ArrayObject $data)
    {
        $config = $this->getConfig();
        $key = Inflector::underscore($this->_table->getAlias());
        if ($this->_table->behaviors()->has('ObjectType') && $this->_table->objectType()) {
             $key = $this->_table->objectType()->get('name');
        }
        $defaults = Configure::read(sprintf('DefaultValues.%s', $key), []);
        $config['fields'] = array_merge($config['fields'], $defaults);
        foreach ($data as $key => $value) {
            if (($value === null || $value === '') && isset($config['fields'][$key])) {
                $data[$key] = $config['fields'][$key];
            }
        }
    }
}
