<?php

namespace CUPPublicBusinessModule\Form;

use Zend\Form\Form;

class AssociationCodeForm extends Form
{
    public function __construct()
    {
        parent::__construct('business-association-form');
        $this->setAttribute('method', 'post');

        $this->add([
            'name'       => 'code',
            'type'       => 'Zend\Form\Element\Text',
            'attributes' => [
                'id'       => 'code',
                'maxlength' => 12,
                'class'    => 'form-control',
                'required' => 'required'
            ]
        ]);
    }
}
