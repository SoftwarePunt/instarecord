<?php

namespace SoftwarePunt\Instarecord\Reflection;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use SoftwarePunt\Instarecord\Attributes\TableName;
use SoftwarePunt\Instarecord\Config\ConfigException;
use SoftwarePunt\Instarecord\Model;

/**
 * Utility for performing reflection on a Model.
 */
class ReflectionModel
{
    /**
     * An instance of the model being reflected.
     */
    protected Model $model;

    /**
     * The reflection class for the model instance.
     */
    protected ReflectionClass $rfClass;

    /**
     * A list of all public, non-static reflection properties.
     *
     * @var ReflectionProperty[]
     */
    protected array $rfPublicProps;

    /**
     * @var mixed[]
     */
    protected array $defaultValues;

    /**
     * @var string|null
     */
    public readonly ?string $nameOverride;

    /**
     * ReflectionModel constructor.
     *
     * @param Model $model
     *
     * @throws ReflectionException
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->rfClass = new ReflectionClass($model);
        $this->rfPublicProps = [];
        $this->defaultValues = [];

        // Filter reflection properties to public, non-static ones only (these are our target properties)
        $allProps = $this->rfClass->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($allProps as $rfProp) {
            if ($rfProp->isPublic() && !$rfProp->isStatic()) {
                $this->rfPublicProps[] = $rfProp;
            }
        }

        // Index default values
        foreach ($this->rfClass->getDefaultProperties() as $name => $value) {
            $this->defaultValues[$name] = $value;
        }

        // Get class attributes
        $attributes = $this->rfClass->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === TableName::class) {
                $this->nameOverride = $attribute->getArguments()[0];
            }
        }
        if (!isset($this->nameOverride)) {
            $this->nameOverride = null;
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Reflection getter helpers

    /**
     * Gets the model's fully qualified class name.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->rfClass->getName();
    }

    /**
     * Gets a list of all public, non-static property names.
     *
     * @return string[]
     */
    public function getPropertyNames(): array
    {
        $propNames = [];

        foreach ($this->rfPublicProps as $rfProp) {
            $propNames[] = $rfProp->getName();
        }

        return $propNames;
    }

    /**
     * Gets a list of reflection properties, indexed by name.
     *
     * @return ReflectionProperty[]
     */
    public function getReflectionProperties(): array
    {
        $propList = [];

        foreach ($this->rfPublicProps as $rfProp) {
            $propList[$rfProp->getName()] = $rfProp;
        }

        return $propList;
    }

    /**
     * @param string $propName
     * @return mixed|string|null
     */
    public function getPropertyDefaultValue(string $propName)
    {
        return $this->defaultValues[$propName] ?? null;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Static helper

    /**
     * Attempts to create a ReflectionModel given a $modelClassName.
     *
     * @param string $modelClassName The fully-qualified class name of the Model class.
     * @return ReflectionModel
     *
     * @throws ConfigException
     * @throws ReflectionException
     */
    public static function fromClassName(string $modelClassName): ReflectionModel
    {
        if (!class_exists($modelClassName)) {
            throw new ConfigException("Cannot create ReflectionModel for invalid class name: {$modelClassName}");
        }

        $referenceModel = (new ReflectionClass($modelClassName))->newInstanceWithoutConstructor();

        if (!$referenceModel instanceof Model) {
            throw new ConfigException(
                "Cannot create ReflectionModel for class that does not extend from Model: {$modelClassName}");
        }

        return new ReflectionModel($referenceModel);
    }
}