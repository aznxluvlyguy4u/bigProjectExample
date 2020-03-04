<?php


namespace AppBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class LessThanOrEqualDecimalCount extends Constraint
{
    public $message = 'decimal.limit.max.exceeded';
//    public $message = 'The decimal count is {{ number }} and exceeds the max decimal count of {{ limit }}.';

    /** @var $max max decimal count */
    public $max;


    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }


    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'max';
    }

    public function __construct($options = null)
    {
        if (null !== $options && !is_array($options)) {
            $options = array(
                'max' => $options,
            );
        }

        parent::__construct($options);

        if (null === $this->max) {
            throw new MissingOptionsException(sprintf('Option "max" must be given for constraint %s', __CLASS__), array('max'));
        }

        if (!ctype_digit($this->max) && !is_int($this->max)) {
            throw new InvalidOptionsException('LessThanOrEqualDecimalCountValidator max option must be an integer', [$this->max]);
        }
    }
}
