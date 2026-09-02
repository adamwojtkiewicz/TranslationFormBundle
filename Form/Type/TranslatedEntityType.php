<?php

/*
 * This file is part of the TranslationFormBundle package.
 *
 * (c) David ALLIX <http://a2lix.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace A2lix\TranslationFormBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Translated entity.
 *
 * @author David ALLIX
 */
class TranslatedEntityType extends AbstractType
{
    private $request;
    private $requestStack;

    // BC for SF 2.3
    public function setRequest(?Request $request = null): void
    {
        $this->request = $request;
    }

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // BC for SF < 2.7
        $optionProperty = 'choice_label';
        if (\in_array('property', $resolver->getDefinedOptions())) {
            $optionProperty = 'property';
        }

        $resolver->setDefaults([
            'translation_path' => 'translations',
            'translation_property' => null,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('e')
                    ->select('e, t')
                    ->join('e.translations', 't');
            },
            $optionProperty => function (Options $options) {
                return $options['translation_path'].'['.$this->getLocale().'].'.$options['translation_property'];
            },
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'a2lix_translatedEntity';
    }

    private function getLocale(): string
    {
        if ($this->requestStack && null !== ($request = $this->requestStack->getCurrentRequest())) {
            return $request->getLocale();
        }

        if ($this->request) {
            return $this->request->getLocale();
        }

        throw new \RuntimeException('Error while getting request');
    }
}
