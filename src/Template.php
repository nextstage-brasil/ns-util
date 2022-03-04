<?php

namespace NsUtil;

/*
 * This file is part of the Text_Template package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A simple template engine.
 *
 * @since Class available since Release 1.0.0
 */
class Template {

    /**
     * @var string
     */
    protected $template = '';

    /**
     * @var string
     */
    protected $openDelimiter = '{';

    /**
     * @var string
     */
    protected $closeDelimiter = '}';

    /**
     * @var array
     */
    protected $values = array();

    /**
     * Constructor.
     *
     * @param  string                   $file
     * @throws InvalidArgumentException
     */
    public function __construct($template_file_or_html, $values = [], $openDelimiter = '{', $closeDelimiter = '}') {
        if (file_exists($template_file_or_html)) {
            $this->setFile($template_file_or_html);
        } else {
            $this->template = $template_file_or_html;
        }
        $this->openDelimiter = $openDelimiter;
        $this->closeDelimiter = $closeDelimiter;
        $this->setVar($values);
    }

    public function setTemplate($text) {
        $this->template = $text;
    }

    public function getTemplate() {
        return $this->template;
    }

    /**
     * Sets the template file.
     *
     * @param  string                   $file
     * @throws InvalidArgumentException
     */
    public function setFile($file) {
        $distFile = $file;
        if (file_exists($file)) {
            $this->template = file_get_contents($file);
        } else if (file_exists($distFile)) {
            $this->template = file_get_contents($distFile);
        } else {
            throw new InvalidArgumentException(
            'Template file could not be loaded.'
            );
        }
    }

    /**
     * Sets one or more template variables.
     *
     * @param array $values
     * @param bool  $merge
     */
    public function setVar(array $values, $merge = TRUE) {
        if (!$merge || empty($this->values)) {
            $this->values = $values;
        } else {
            $this->values = array_merge($this->values, $values);
        }
    }

    /**
     * Renders the template and returns the result.
     *
     * @return string
     */
    public function render() {
        $keys = array();
        foreach ($this->values as $key => $value) {
            $keys[] = $this->openDelimiter . $key . $this->closeDelimiter;
        }
        //Log::logTxt('debug', $value);
        $this->template = str_replace($keys, $this->values, $this->template);
        return $this->template;
    }

    /**
     * Renders the template and writes the result to a file.
     *
     * @param string $target
     */
    public function renderTo($target) {
        $fp = @fopen($target, 'wt');
        if ($fp) {
            fwrite($fp, $this->render());
            fclose($fp);
        } else {
            $error = error_get_last();
            throw new RuntimeException(
            sprintf(
                    'Could not write to %s: %s', $target, substr((string)
                            $error['message'], strpos($error['message'], ':') + 2
                    )
            )
            );
        }
    }

}
