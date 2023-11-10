<?php

namespace Hexafuchs\LaminasSecurity\Services;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class ConsoleStyle
{
    const MAX_LINE_LENGTH = 120;
    private int $lineLength;

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(
        private readonly OutputInterface $output
    )
    {
        $width            = (new Terminal())->getWidth() ?: self::MAX_LINE_LENGTH;
        $this->lineLength = min($width - (int)(DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);

        // Register custom formatter styles
        $colors = [
            'fail'    => 'red',
            'warn'    => 'yellow',
            'success' => 'green',
            'skip'    => 'cyan',
            'mark'    => 'magenta',
        ];

        foreach ($colors as $name => $color) {
            $styles = [
                $name           => new OutputFormatterStyle($color),
                'bold-' . $name => new OutputFormatterStyle($color, options: ['bold']),
                'cmd-' . $name  => new OutputFormatterStyle($color, 'gray')
            ];

            foreach ($styles as $styleName => $style) {
                $this->output->getFormatter()->setStyle($styleName, $style);

                if ($this->output instanceof ConsoleOutputInterface) {
                    $this->output->getErrorOutput()->getFormatter()->setStyle($styleName, $style);
                }
            }
        }
    }

    /**
     * Prints the application-header, should only be called once per run.
     *
     * @param string $title
     * @return void
     */
    public function title(string $title): void
    {
        $indent = str_repeat(' ', floor(($this->lineLength - 55) / 2));

        $this->output->writeln([
            '<bold-mark>' .
            str_repeat('-', $this->lineLength),
            $indent . '▗▖               █                       ▗▄▖           ',
            $indent . '▐▌               ▀                      ▗▛▀▜           ',
            $indent . '▐▌    ▟██▖▐█▙█▖ ██  ▐▙██▖ ▟██▖▗▟██▖     ▐▙    ▟█▙  ▟██▖',
            $indent . '▐▌    ▘▄▟▌▐▌█▐▌  █  ▐▛ ▐▌ ▘▄▟▌▐▙▄▖▘      ▜█▙ ▐▙▄▟▌▐▛  ▘',
            $indent . '▐▌   ▗█▀▜▌▐▌█▐▌  █  ▐▌ ▐▌▗█▀▜▌ ▀▀█▖ ██▌    ▜▌▐▛▀▀▘▐▌   ',
            $indent . '▐▙▄▄▖▐▙▄█▌▐▌█▐▌▗▄█▄▖▐▌ ▐▌▐▙▄█▌▐▄▄▟▌     ▐▄▄▟▘▝█▄▄▌▝█▄▄▌',
            $indent . '▝▀▀▀▘ ▀▀▝▘▝▘▀▝▘▝▀▀▀▘▝▘ ▝▘ ▀▀▝▘ ▀▀▀       ▀▀▘  ▝▀▀  ▝▀▀ ',
            str_repeat('-', $this->lineLength),
            str_pad($title, $this->lineLength, ' ', STR_PAD_BOTH),
            str_repeat('-', $this->lineLength),
            '</>'
        ]);
    }

    /**
     * Print a divider
     *
     * @param string $name
     * @return void
     */
    public function section(string $name): void
    {
        $this->output->writeln([
            '<mark>',
            ' » ' . $name,
            str_repeat('-', $this->lineLength) . '</>',
        ]);
    }

    /**
     * Create a base table
     *
     * @param array $headers
     * @param array $rows
     * @return \Symfony\Component\Console\Helper\Table
     */
    protected function createTable(array $headers, array $rows): Table
    {
        return (new Table($this->output))
            ->setHeaders($headers)
            ->setRows($rows);
    }

    /**
     * Prints tabular data with headers on the top
     *
     * @param array $headers
     * @param array $rows
     * @return void
     */
    public function table(array $headers, array $rows): void
    {
        $this->output->writeln('');
        $this->createTable($headers, $rows)
            ->setStyle('box')
            ->render();
        $this->output->writeln('');
    }

    /**
     * Prints tabular data with headers on the left
     *
     * @param array $headers
     * @param array $rows
     * @return void
     */
    public function horizontalTable(array $headers, array $rows): void
    {
        $this->output->writeln('');
        $this->createTable($headers, $rows)
            ->setStyle('borderless')
            ->setHorizontal()
            ->render();
        $this->output->writeln('');
    }

    /**
     * Indicate a running process and replace the indicator with the output after the process finishes.
     *
     * @param string $message
     * @param callable $resultGenerator
     * @return void
     */
    public function indicator(string $message, callable $resultGenerator): void
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            $this->output->getErrorOutput()->write($message . "\r");

            $result         = $resultGenerator();
            $message_length = strlen(strip_tags($message));
            $result_length  = strlen(strip_tags($result));
            $difference     = ($message_length > $result_length) ? ($message_length - $result_length) : 0;

            $this->output->writeln($result . str_repeat(' ', $difference));
        } else {
            $this->output->writeln($resultGenerator());
        }
    }

    /**
     * Print the given line(s)
     *
     * @param iterable|string $messages
     * @return void
     */
    public function writeln(iterable|string $messages): void
    {
        $this->output->writeln($messages);
    }

    /**
     * Indent all Lines by 4 spaces.
     *
     * @param iterable|string $messages
     * @param string|null $style
     * @return void
     */
    public function indent(iterable|string $messages, ?string $style = null): void
    {
        $messages = is_array($messages) ? array_values($messages) : [$messages];
        $lines    = [];
        $indent   = $style === null ? 4 : 6;

        foreach ($messages as $message) {
            $lines = array_merge(
                $lines,
                explode(PHP_EOL, $this->wrap($message, $this->lineLength - $indent))
            );
        }

        foreach ($lines as &$line) {
            $suffix = str_repeat(' ', max($this->lineLength - $indent - strlen(strip_tags($line)), 0));
            $line   = '    ' . $line . $suffix;

            if ($style !== null) {
                $line = sprintf('<%s> %s </>', $style, $line);
            }
        }

        $this->output->writeln($lines);
    }

    /**
     * Create an indent with red text.
     *
     * @param iterable|string $messages
     * @return void
     */
    public function error(iterable|string $messages): void
    {
        $this->indent($messages, 'fg=red');
    }

    /**
     * Create an indent with yellow text.
     *
     * @param iterable|string $messages
     * @return void
     */
    public function warning(iterable|string $messages): void
    {
        $this->indent($messages, 'fg=yellow');
    }

    /**
     * Create an indent with green text.
     *
     * @param iterable|string $messages
     * @return void
     */
    public function success(iterable|string $messages): void
    {
        $this->indent($messages, 'fg=green');
    }

    /**
     * Create an indent with blue text.
     *
     * @param iterable|string $messages
     * @return void
     */
    public function info(iterable|string $messages): void
    {
        $this->indent($messages, 'fg=blue');
    }

    /**
     * Wraps the given text by the given width ignoring styles in the process.
     *
     * This part is taken from Symfonys OutputWrapper that came in version 6.3 but is not available in older versions
     * of symfony/console. To increase compatibility with projects using laminas-cli or older versions of
     * symfony/console, this was copied into this project.
     *
     * @see https://github.com/symfony/console/blob/6.3/Helper/OutputWrapper.php
     *
     * @param string $text
     * @param int $width
     * @return string
     */
    private function wrap(string $text, int $width): string
    {
        $rowPattern = "(?:<(?:(?:[a-z](?:[^\\\\<>]*+ | \\\\.)*)|/(?:[a-z][^<>]*+)?)>|.){1,$width}";
        $pattern    = sprintf('#(?:((?>(%1$s)((?<=[^\S\r\n])[^\S\r\n]?|(?=\r?\n)|$|[^\S\r\n]))|(%1$s))(?:\r?\n)?|(?:\r?\n|$))#imux', $rowPattern);
        $output     = rtrim(preg_replace($pattern, '\\1' . PHP_EOL, $text), PHP_EOL);

        return str_replace(' ' . PHP_EOL, PHP_EOL, $output);
    }
}