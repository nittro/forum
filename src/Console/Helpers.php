<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Question\Question;


class Helpers {

    public static function getPasswordValidator(string $verificationPrompt = null) : callable {
        $verify = new Question($verificationPrompt ?: 'Retype password for verification: ');
        $verify->setNormalizer([static::class, 'trimIfString']);
        $verify->setMaxAttempts(1);
        $verify->setHidden(true);

        return function(?string $password, bool $need = true, InteractionHelper $interactionHelper) use ($verify) {
            if (!$need) {
                return;
            }

            if (empty($password)) {
                throw new \RuntimeException('Password may not be empty');
            }

            $password2 = $interactionHelper->ask($verify);

            if ($password2 !== $password) {
                throw new \RuntimeException('Passwords do not match');
            }
        };
    }

    public static function trimIfString($value) {
        return is_string($value) ? trim($value) : $value;
    }

}
