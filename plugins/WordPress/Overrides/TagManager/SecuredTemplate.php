<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Closure;
use Piwik\Plugins\TagManager\Template\BaseTemplate;
use Piwik\Settings\FieldConfig;
use Piwik\Validators\BaseValidator;

/**
 * Adds extra validators to a Tag Manager template for WordPress users that do
 * not have the `unfiltered_html` capability, and optionally normalises what is
 * stored for a parameter once it has passed them.
 */
trait SecuredTemplate
{
    /**
     * @var BaseTemplate
     */
    private $wrapped;

    /**
     * @var array<string, BaseValidator[]>
     */
    private $extraValidators;

    /**
     * @var array<string, Closure>
     */
    private $extraTransforms;

    /**
     * @param BaseTemplate $wrapped the instance this one is replacing, as Matomo ships it
     * @param array<string, BaseValidator[]> $extraValidators parameter name => validators to add
     * @param array<string, Closure> $extraTransforms parameter name => normalises the value that
     *                                                is stored for it
     */
    public function __construct(BaseTemplate $wrapped, array $extraValidators, array $extraTransforms = [])
    {
        $this->wrapped = $wrapped;
        $this->extraValidators = $extraValidators;
        $this->extraTransforms = $extraTransforms;
    }

    public function getParameters()
    {
        $parameters = parent::getParameters();

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (!empty($this->extraValidators[$name])) {
                foreach ($this->extraValidators[$name] as $validator) {
                    // add the validator to the parameter's cached FieldConfig
                    $parameter->configureField()->validators[] = $validator;
                }
            }

            if (!empty($this->extraTransforms[$name])) {
                $this->addTransform($parameter->configureField(), $this->extraTransforms[$name]);
            }
        }

        return $parameters;
    }

    /**
     * @param FieldConfig $field
     * @param Closure $transform
     */
    private function addTransform(FieldConfig $field, Closure $transform)
    {
        $original = $field->transform;

        $field->transform = function ($value, $setting) use ($original, $transform) {
            if ($original instanceof Closure) {
                $value = call_user_func($original, $value, $setting);
            }

            return call_user_func($transform, $value);
        };
    }

    public function getId()
    {
        return $this->wrapped->getId();
    }

    public function getName()
    {
        return $this->wrapped->getName();
    }

    public function getDescription()
    {
        return $this->wrapped->getDescription();
    }

    public function getHelp()
    {
        return $this->wrapped->getHelp();
    }

    public function loadTemplate($context, $entity)
    {
        // forwarded with func_get_args() because BaseContext::getPreConfiguredVariablesJSCodeResponse()
        // calls this with a third argument that the signature does not declare
        return call_user_func_array([$this->wrapped, 'loadTemplate'], func_get_args());
    }
}
