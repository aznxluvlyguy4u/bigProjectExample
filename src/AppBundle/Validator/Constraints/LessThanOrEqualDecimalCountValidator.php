<?php


namespace AppBundle\Validator\Constraints;


use AppBundle\Util\NumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class LessThanOrEqualDecimalCountValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof LessThanOrEqualDecimalCount) {
            throw new UnexpectedTypeException($constraint, LessThanOrEqualDecimalCount::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_numeric($value)) {
            throw new UnexpectedTypeException($value, 'number');
        }

        $maxDigitCount = intval($constraint->max);
        $valueDigitCount = NumberUtil::getDecimalCount($value);

        if ($maxDigitCount < $valueDigitCount) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%number%', $valueDigitCount)
                ->setParameter('%limit%', $maxDigitCount)
//                ->setParameter('{{ number }}', $valueDigitCount)
//                ->setParameter('{{ limit }}', $maxDigitCount)
                ->addViolation();
        }
    }
}
