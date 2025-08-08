<?php

namespace Nasus\WebmanUtils\Command;

use Illuminate\Database\Query\Builder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('idea:tip', 'Idea Tip')]
/**
 * @method static static whenLike($params, $prefix = null)
 * @method static static whenWhere($params, $prefix = null)
 * @method static static whenOrLike($params, $prefix = null)
 * @method static static whenOrderBy($fieldsMap = [])
 * @method static static whenDate($params,  $prefix = null)
 */
class IdeaTipCommand extends Command
{
    const string methodDoc = '#IDE-START ' . PHP_EOL . '%s ' . PHP_EOL . ' #IDE-END';

    private static function getMethodDoc(): string
    {
        $methodRef = new \ReflectionClass(self::class);
        return sprintf(self::methodDoc, $methodRef->getDocComment());
    }

    /**
     * @return void
     */
    public static function extracted(): void
    {
        $builderRef = new \ReflectionClass(Builder::class);
        $builderContent = file_get_contents($builderRef->getFileName());
        $docStr = self::getMethodDoc();

        if ($builderRef->getDocComment()) {
            $builderContent = preg_replace('/#IDE-START[\s\S]*?#IDE-END/', $docStr, $builderContent);
        } else {
            $builderContent = str_replace('class Builder', $docStr . PHP_EOL . 'class Builder', $builderContent);
        }

        file_put_contents($builderRef->getFileName(), $builderContent);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        config('app.debug') && self::extracted();
        return self::SUCCESS;
    }

    /**
     * composer installs with a script
     */
    public static function installTips(): void
    {
        self::extracted();
    }
}
