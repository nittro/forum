<?php

declare(strict_types=1);

namespace App\UI\Latte;

use Latte\CompileException;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;


class Macros extends MacroSet {

    public static function install(Compiler $compiler) : void {
        $me = new static($compiler);
        $me->addMacro('datetime', null, [$me, 'macroDatetime'], null, self::AUTO_EMPTY);
    }


    public function macroDatetime(MacroNode $node, PhpWriter $writer) : void {
        if ($node->prefix !== MacroNode::PREFIX_NONE) {
            throw new CompileException('Unknown macro ' . $node->getNotation() . ', did you mean n:' . $node->name . '?');
        } else if (strtolower($node->htmlNode->name) !== 'time') {
            throw new CompileException('Macro ' . $node->getNotation() . ' can only be applied to <time> elements');
        }

        $node->attrCode = $writer->write(' datetime="<?php echo %escape(%node.word->format(\'c\')) ?>"');
        $node->innerContent = $writer->write('<?php echo %escape(call_user_func($this->filters->formatdate, %node.word, %node.args?)) ?>');
    }

}
