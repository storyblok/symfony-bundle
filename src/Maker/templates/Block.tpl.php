<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements() ?>

#[AsBlock(name: '<?= $block_name ?>', template: '<?= $block_template ?>')]
final readonly class <?= $class_data->getClassName() ?><?= "\n" ?>
{
    use ValueObjectTrait;

<?php foreach ($properties as $property): ?>
<?php if (!$property->isUnmapped()): ?>
<?php if (null !== $property->phpDoc()): ?>
    <?= $property->phpDoc() ?>

<?php endif; ?>
    <?= $property->declaration() ?>

<?php endif; ?>
<?php endforeach; ?>

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
<?php foreach ($properties as $property): ?>
<?php if ($property->isUnmapped()): ?>
        <?= $property->todo() ?>

<?php else: ?>
        <?= $property->assignment() ?>

<?php endif; ?>
<?php endforeach; ?>
    }
}
