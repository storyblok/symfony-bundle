<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements(); ?>

#[AsBlock]
<?= $class_data->getClassDeclaration() ?>

{
    use ValueObjectTrait;

<?php foreach ($properties as $property): ?>
<?php if (\in_array($property->type->value, ['blocks', 'list'])): ?>
    /**
     * @var list<<?= $property->class ?? 'object' ?>>
     */
<?php endif; ?>
    <?= $property->toString() ?><?= "\n" ?>
<?php endforeach; ?>

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
<?php foreach ($properties as $property): ?>
<?php if ($property->nullable): ?>
        <?= \sprintf('$this->%s = self::nullOr%s($values, \'%s\');', $property->name, \ucfirst($property->typehint()), \Symfony\Bundle\MakerBundle\Str::asSnakeCase($property->name)) ?>
<?php else: ?>
        <?= \sprintf('$this->%s = self::%s($values, \'%s\');', $property->name, $property->typehint(), \Symfony\Bundle\MakerBundle\Str::asSnakeCase($property->name)) ?>
<?php endif; ?>
<?= \PHP_EOL ?>
<?php endforeach; ?>

    }
}
