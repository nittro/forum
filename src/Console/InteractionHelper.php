<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;


class InteractionHelper {

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /** @var QuestionHelper */
    private $questionHelper;



    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper) {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $questionHelper;
    }

    public function ensureArgumentIsValid(string $name, callable $validator, string $question, bool $hidden = false) : void {
        $this->ensureInputIsValid('Argument', ... func_get_args());
    }

    public function ensureOptionIsValid(string $name, callable $validator, string $question, bool $hidden = false) : void {
        $this->ensureInputIsValid('Option', ... func_get_args());
    }

    private function ensureInputIsValid(string $method, string $name, callable $validator, string $question, bool $hidden = false) : void {
        $this->validateInitialInput($method, $name, $validator);

        if (!$this->input->{'get' . $method}($name)) {
            $question = new Question($question . ($hidden ? ' ' : "\n"));
            $question->setHidden($hidden);
            $question
                ->setNormalizer([Helpers::class, 'trimIfString'])
                ->setValidator(function($v) use ($validator) {
                    call_user_func($validator, $v, true, $this);
                    return $v;
                });

            $value = $this->ask($question);
            $this->input->{'set' . $method}($name, $value);
        }
    }

    public function ask(Question $question) {
        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function validateInitialInput(string $method, string $name, callable $validator) : void {
        try {
            call_user_func($validator, $this->input->{'get' . $method}($name), false, $this);
        } catch (\RuntimeException $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->input->{'set' . $method}($name, null);
        }
    }


}
