<?php

namespace AppBundle\Form;

use Doctrine\ORM\QueryBuilder;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class ItemFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('text', Filters\TextFilterType::class, [
            'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                if (empty($values['value'])) {
                    return null;
                }

                /** @var QueryBuilder $queryBuilder */
                $queryBuilder = $filterQuery->getQueryBuilder();
                $orX = $queryBuilder->expr()->orX();

                foreach (['id', 'name', 'lastName', 'ringSize', 'email', 'telephone', 'city', 'date'] as $field) {
                    $orX->add($queryBuilder->expr()->like($values['alias'].'.'.$field, ':search_param'));
                }

                $queryBuilder->andWhere($orX);
                $queryBuilder->setParameter('search_param', '%'.$values['value'].'%');
            },
        ]);
    }

    public function getBlockPrefix()
    {
        return 'filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'validation_groups' => array('filtering') // avoid NotBlank() constraint-related message
        ));
    }
}
