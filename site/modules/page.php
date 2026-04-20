<?php

class Page
{
    private $template;

    public function __construct($template)
    {
        $this->template = $template;
    }

    public function Render($data)
    {
        if (!is_file($this->template)) {
            throw new RuntimeException('Template not found: ' . $this->template);
        }

        $output = file_get_contents($this->template);

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $safeValue = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $output = str_replace($placeholder, $safeValue, $output);
        }

        return $output;
    }
}
