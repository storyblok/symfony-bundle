<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

enum <?= $class_name ?>: string
{
<?php foreach ($cases as $case => $value): ?>
    case <?= $case ?> = '<?= \str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value) ?>';
<?php endforeach; ?>
}
