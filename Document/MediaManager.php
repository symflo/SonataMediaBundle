<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sonata\MediaBundle\Document;

use Sonata\MediaBundle\Model\MediaManager as AbstractMediaManager;
use Sonata\MediaBundle\Model\MediaInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Sonata\MediaBundle\Provider\Pool;

class MediaManager extends AbstractMediaManager
{
    protected $dm;
    protected $repository;
    protected $class;

    /**
     * @param \Sonata\MediaBundle\Provider\Pool $pool
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param $class
     */
    public function __construct(Pool $pool, DocumentManager $dm, $class)
    {
        $this->dm    = $dm;

        parent::__construct($pool, $class);
    }

    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->dm->getRepository($this->class);
        }

        return $this->repository;
    }

    /**
     * Updates a media
     *
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param string $context
     * @param string $providerName
     * @return void
     */
    public function save(MediaInterface $media, $context = null, $providerName = null)
    {
        if ($context) {
            $media->setContext($context);
        }

        if ($providerName) {
            $media->setProviderName($providerName);
        }

        $isNew = $media->getId() != null;

        $formats  = $this->pool->getFormatNamesByContext($media->getContext());

        $provider = $this->pool->getProvider($media->getProviderName());
        $provider->setFormats($formats);

        if ($isNew) {
            $provider->prePersist($media);
        } else {
            $provider->preUpdate($media);
        }

        $this->dm->persist($media);
        $this->dm->flush();

        if ($isNew) {
            $provider->postPersist($media);
        } else {
            $provider->postUpdate($media);
        }

        // just in case the pool alter the media
        $this->dm->persist($media);
        $this->dm->flush();
    }

    /**
     * Deletes a media
     *
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @return void
     */
    public function delete(MediaInterface $media)
    {
        $formats  = $this->pool->getFormatNamesByContext($media->getContext());

        $provider = $this->pool->getProvider($media->getProviderName());
        $provider->setFormats($formats);

        $provider->preRemove($media);
        $this->dm->remove($media);
        $this->dm->flush();

        $provider->postRemove($media);
        $this->dm->flush();
    }
}
