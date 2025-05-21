<?php
namespace App\Lib;

class View{
    protected string $templateFile;
    protected array $data = [];

    public function __construct(string $templateFile, array $data){
        if(!file_exists($templateFile)){
            trigger_error("Template file not found: " . $templateFile, E_USER_ERROR);
            $this->templateFile = '';
            return;
        }
        $this->templateFile = $templateFile;
        $this->data = $data;
    }

    public function setData(array $data) : void {
        $this->data = array_merge($this->data, $data);
    }

    public function set(string $key, $value): void {
        $this->data[$key] = $value;
    }

    public function render() : string {
        if (empty($this->templateFile)) {
            return "Error: Template file not set or not found";
        }
        extract($this->data);

        ob_start();
        require $this->templateFile;
        return ob_get_clean();
    }

    public static function make(string $templateFile, array $data = []): string {
        $view = new self($templateFile, $data);
        return $view->render();
    }
}