<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */
namespace Tritoq\Bundle\CieloBundle\Validation;


use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Type;

/**
 *
 * Descrição da Classe aqui
 *
 *
 * Class ValidationFactory
 *
 * @category  Library
 * @copyright Artur Magalhães <nezkal@gmail.com>
 * @package   Tritoq\Bundle\CieloBundle\Validation
 * @license   GPL-3.0+
 */
class ValidationFactory
{

    /**
     * @param      $type
     * @param      $value
     * @param null $options
     *
     * @throws \Exception
     * @return ConstraintEncapsuled
     */
    public static function createConstraint($type, $value, $options = null)
    {
        $constraintEncapsuled = new ConstraintEncapsuled();
        $constraint = null;

        switch ($type) {

            case 'choice':
                $constraint = new Choice();
                if (isset($options) && is_array($options)) {
                    $constraint->choices = $options;
                }
                break;

            case 'instance':

                $constraint = new EqualTo();
                if (isset($options['class'])) {
                    $constraint->value = $options['class'];
                }

                if (is_object($value)) {
                    $value = get_class($value);
                }

                break;

            case 'date':
                $constraint = new Date();
                break;

            case 'number':
                $constraint = new Type(array(
                    'type' => 'float'
                ));

                break;

            default:
                throw new \Exception('The type ' . $type . ' is not supported');
        }

        $constraintEncapsuled->value = $value;
        $constraintEncapsuled->constraint = $constraint;


        return $constraintEncapsuled;
    }
}
