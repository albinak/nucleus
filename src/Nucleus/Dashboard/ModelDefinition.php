<?php

namespace Nucleus\Dashboard;

use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\ConstraintViolationList;

class ModelDefinition
{
    protected $className;

    protected $name;

    protected $fields = array();

    protected $actions = array();

    public function setClassName($className)
    {
        $this->className = trim($className, '\\');
        return $this;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function isAutoGenerated()
    {
        return $this->className === null;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setFields(array $fields)
    {
        $this->fields = array();
        array_map(array($this, 'addField'), $fields);
        return $this;
    }

    public function addField(FieldDefinition $field)
    {
        $this->fields[] = $field;
        return $this;
    }

    public function getField($name)
    {
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }
        return false;
    }

    public function getIdentifierField()
    {
        foreach ($this->fields as $field) {
            if ($field->isIdentifier()) {
                return $field;
            }
        }
        return false;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getListableFields()
    {
        return array_filter($this->fields, function($f) { return $f->isListable(); });
    }

    public function getEditableFields()
    {
        return array_filter($this->fields, function($f) { return $f->isEditable(); });
    }

    public function setActions(array $actions)
    {
        $this->actions = array();
        array_map(array($this, 'addAction'), $actions);
        return $this;
    }

    public function addAction(ActionDefinition $action)
    {
        $this->actions[] = $action;
        return $this;
    }

    public function getAction($name)
    {
        foreach ($this->actions as $action) {
            if ($action->getName() === $name) {
                return $action;
            }
        }
        return false;
    }
    
    public function getActions()
    {
        return $this->actions;
    }

    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function validate($data)
    {
        if ($this->validator === null) {
            return true;
        }

        if ($this->className !== null) {
            $violiations = $this->validator->validate($data);
        } else {
            $violiations = new ConstraintViolationList();
            foreach ($this->fields as $field) {
                $value = array_key_exists($field->getProperty(), $data) ? $data[$field->getProperty()] : null;
                $violiations->addAll($this->validator->validateValue($value, $field->getConstraints()));
            }
        }

        if (count($violiations)) {
            throw new ValidationException($violiations);
        }
    }
}
