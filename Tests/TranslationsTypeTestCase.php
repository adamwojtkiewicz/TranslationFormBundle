<?php

/*
 * This file is part of the TranslationFormBundle package.
 *
 * (c) David ALLIX <http://a2lix.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace A2lix\TranslationFormBundle\Tests;

use A2lix\TranslationFormBundle\Form\EventListener\TranslationsFormsListener;
use A2lix\TranslationFormBundle\Form\EventListener\TranslationsListener;
use A2lix\TranslationFormBundle\Form\Type\TranslationsFieldsType;
use A2lix\TranslationFormBundle\Form\Type\TranslationsFormsType;
use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use A2lix\TranslationFormBundle\Locale\DefaultProvider;
use A2lix\TranslationFormBundle\TranslationForm\TranslationForm;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class TranslationsTypeTestCase extends TypeTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $emRegistry;

    protected function setUp(): void
    {
        $configuration = ORMSetup::createAttributeMetadataConfiguration(
            [__DIR__.'/Gedmo/Fixtures/Entity'],
            true
        );
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $configuration);
        $this->em = new EntityManager($connection, $configuration);
        $this->emRegistry = $this->getEmRegistry($this->em);

        $schemaTool = new SchemaTool($this->em);

        $classes = [];
        foreach ($this->getUsedEntityFixtures() as $class) {
            $classes[] = $this->em->getClassMetadata($class);
        }

        try {
            $schemaTool->dropSchema($classes);
            $schemaTool->createSchema($classes);
        } catch (\Exception $e) {
        }

        $this->dispatcher = new EventDispatcher();
        parent::setUp();

        $formExtensions = [new DoctrineOrmExtension($this->emRegistry)];
        $resolvedFormTypeFactory = $this->createStub('Symfony\Component\Form\ResolvedFormTypeFactory');

        $formRegistry = new FormRegistry($formExtensions, $resolvedFormTypeFactory);
        $translationForm = new TranslationForm($formRegistry, $this->emRegistry);
        $translationsListener = new TranslationsListener($translationForm);
        $translationsFormsListener = new TranslationsFormsListener();

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions(
                $formExtensions
            )
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->addTypeGuesser(
                $this->createStub('Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser')
            )
            ->addTypes([
                new TranslationsType($translationsListener, new DefaultProvider(['fr', 'en', 'de'], 'en')),
                new TranslationsFieldsType(),
                new TranslationsFormsType(
                    $translationForm,
                    $translationsFormsListener,
                    new DefaultProvider(['fr', 'en', 'de'], 'en')
                ),
            ])
            ->getFormFactory();

        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em = null;
        $this->emRegistry = null;
    }

    protected function getUsedEntityFixtures(): array
    {
        return [];
    }

    protected function persist(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }

        $this->em->flush();
        // no clear, because entities managed by the choice field must
        // be managed!
    }

    protected function getEmRegistry($em): Registry
    {
        $container = new Container();
        $container->set('doctrine.orm.default_entity_manager', $em);

        return new Registry($container, [], ['default' => 'doctrine.orm.default_entity_manager'], 'default', 'default');
    }
}
