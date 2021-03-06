<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;

/**
 * Class CurriculumItemRelUserCollectionType
 * @package ChamiloLMS\CoreBundle\Form
 */
class CurriculumItemRelUserCollectionType extends AbstractType
{
    public $itemId;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('userItems', 'collection',
            array (
                'type' => new CurriculumItemRelUserType($this->itemId),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
                'options' => array(
                    // options on the rendered CurriculumItemRelUserType
                ),
                'label' => ' '
            )
        );

        // Save button per item
        //$builder->add('submit', 'submit', array('attr' => array('class' => 'btn btn-success', 'onclick' => 'save(this);')));
    }

    public function __construct($itemId = null)
    {
        $this->itemId = $itemId;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'ChamiloLMS\CoreBundle\Entity\CurriculumItem'
            )
        );
    }


    public function getName()
    {
        return 'CurriculumItemRelUserCollection';
    }
}
