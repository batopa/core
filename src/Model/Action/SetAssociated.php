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

namespace BEdita\Core\Model\Action;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;

/**
 * Command to replace all entities associated to another entity.
 *
 * @since 4.0.0
 */
class SetAssociated extends UpdateAssociated
{

    /**
     * Count entities to be actually updated.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param \Cake\Datasource\EntityInterface[] $relatedEntities Related entities.
     * @return int
     */
    protected function diff(EntityInterface $entity, $relatedEntities)
    {
        $bindingKey = (array)$this->Association->bindingKey();
        $existing = $this->existing($entity);

        $diff = 0;
        $new = [];
        foreach ($relatedEntities as $relatedEntity) {
            $primaryKey = $relatedEntity->extract($bindingKey);
            $new[] = $primaryKey;
            if (in_array($primaryKey, $existing)) {
                continue;
            }

            $diff++;
        }
        foreach ($existing as $primaryKey) {
            if (in_array($primaryKey, $new)) {
                continue;
            }

            $diff++;
        }

        return $diff;
    }

    /**
     * Replace existing relations.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param \Cake\Datasource\EntityInterface|\Cake\Datasource\EntityInterface[]|null $relatedEntities Related entity(-ies).
     * @return int|false Number of updated relationships, or `false` on failure.
     * @throws \RuntimeException Throws an exception if an unsupported association is passed.
     */
    public function __invoke(EntityInterface $entity, $relatedEntities)
    {
        if ($this->Association instanceof BelongsToMany || $this->Association instanceof HasMany) {
            if ($relatedEntities === null) {
                $relatedEntities = [];
            } elseif (!is_array($relatedEntities)) {
                $relatedEntities = [$relatedEntities];
            }

            return $this->toMany($entity, $relatedEntities);
        }

        if ($relatedEntities !== null && !($relatedEntities instanceof EntityInterface)) {
            throw new \InvalidArgumentException(__('Unable to link multiple entities'));
        }

        if ($this->Association instanceof BelongsTo) {
            return $this->Association->connection()->transactional(function () use ($entity, $relatedEntities) {
                return $this->belongsTo($entity, $relatedEntities);
            });
        }

        if ($this->Association instanceof HasOne) {
            return $this->Association->connection()->transactional(function () use ($entity, $relatedEntities) {
                return $this->hasOne($entity, $relatedEntities);
            });
        }

        throw new \RuntimeException(__('Unknown association of type "{0}"', get_class($this->Association)));
    }

    /**
     * Process action for to-many relationships.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param \Cake\Datasource\EntityInterface[] $relatedEntities Related entities.
     * @return int|false
     */
    protected function toMany(EntityInterface $entity, array $relatedEntities)
    {
        $count = $this->diff($entity, $relatedEntities); // This doesn't need to be in a transaction.

        if ($this->Association instanceof HasMany) {
            return $this->Association->replace($entity, $relatedEntities) ? $count : false;
        }

        if ($this->Association instanceof BelongsToMany) {
            return $this->Association->replaceLinks($entity, $relatedEntities) ? $count : false;
        }

        return false;
    }

    /**
     * Process action for "belongs to" relationships.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param \Cake\Datasource\EntityInterface|null $relatedEntity Related entity.
     * @return int|false
     */
    protected function belongsTo(EntityInterface $entity, EntityInterface $relatedEntity = null)
    {
        $existing = $this->existing($entity);

        if ($existing === null && $relatedEntity === null) {
            return 0;
        } elseif ($relatedEntity !== null) {
            $bindingKey = $relatedEntity->extract((array)$this->Association->bindingKey());

            if ($bindingKey == $existing) {
                return 0;
            }
        }

        $entity->set($this->Association->property(), $relatedEntity);

        return $this->Association->source()->save($entity) ? 1 : false;
    }

    /**
     * Process action for "has one" relationships.
     *
     * @param \Cake\Datasource\EntityInterface $entity Source entity.
     * @param \Cake\Datasource\EntityInterface|null $relatedEntity Related entity.
     * @return int|false
     */
    protected function hasOne(EntityInterface $entity, EntityInterface $relatedEntity = null)
    {
        $existing = $this->existing($entity);

        if ($existing === null && $relatedEntity === null) {
            return 0;
        } elseif ($relatedEntity !== null) {
            $primaryKey = $relatedEntity->extract((array)$this->Association->primaryKey());

            if ($primaryKey == $existing) {
                return 0;
            }
        }

        $relatedEntity->set(array_combine(
            (array)$this->Association->foreignKey(),
            $entity->extract((array)$this->Association->bindingKey())
        ));

        return $this->Association->target()->save($relatedEntity) ? 1 : false;
    }
}
