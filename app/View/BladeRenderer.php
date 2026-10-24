<?php

namespace App\View;

class BladeRenderer
{
    protected $viewsPath;
    protected $sections = [];
    protected $currentSection = null;
    protected $layout = null;

    public function __construct()
    {
        $this->viewsPath = realpath(__DIR__ . '/../../resources/views');
    }

    public function render($viewName, $data = [])
    {
        $filePath = $this->resolvePath($viewName);
        if (!$filePath || !file_exists($filePath)) {
            throw new \Exception("View [$viewName] not found at path [$filePath].");
        }

        extract($data);

        // Read template and compile Blade directives to PHP
        $content = file_get_contents($filePath);
        $compiled = $this->compile($content);

        // Execute compiled PHP in isolated buffer
        ob_start();
        eval('?>' . $compiled);
        $rendered = ob_get_clean();

        // If template extends a layout
        if ($this->layout) {
            $layoutPath = $this->resolvePath($this->layout);
            $layoutContent = file_get_contents($layoutPath);
            $compiledLayout = $this->compile($layoutContent);

            ob_start();
            eval('?>' . $compiledLayout);
            $rendered = ob_get_clean();
        }

        return $rendered;
    }

    protected function resolvePath($viewName)
    {
        $relative = str_replace('.', '/', $viewName) . '.blade.php';
        return $this->viewsPath . DIRECTORY_SEPARATOR . $relative;
    }

    protected function compile($content)
    {
        // 0. Blade Comments: {{-- comment --}}
        $content = preg_replace('/\{\{--(.*?)--\}\}/s', '', $content);

        // 1. @extends('layouts.app')
        $content = preg_replace_callback('/@extends\([\'"]([^\'"]+)[\'"]\)/', function ($m) {
            return '<?php $this->layout = "' . $m[1] . '"; ?>';
        }, $content);

        // 2. @section('content') ... @endsection
        $content = preg_replace_callback('/@section\([\'"]([^\'"]+)[\'"]\)/', function ($m) {
            return '<?php ob_start(); $this->currentSection = "' . $m[1] . '"; ?>';
        }, $content);

        $content = preg_replace('/@endsection/', '<?php $this->sections[$this->currentSection] = ob_get_clean(); ?>', $content);

        // 3. @yield('content', 'default')
        $content = preg_replace_callback('/@yield\([\'"]([^\'"]+)[\'"](?:,\s*[\'"]([^\'"]*)[\'"])?\)/', function ($m) {
            $default = isset($m[2]) ? $m[2] : '';
            return '<?php echo isset($this->sections["' . $m[1] . '"]) ? $this->sections["' . $m[1] . '"] : "' . addslashes($default) . '"; ?>';
        }, $content);

        // 4. @include('partials.name')
        $content = preg_replace_callback('/@include\([\'"]([^\'"]+)[\'"]\)/', function ($m) {
            $includePath = $this->resolvePath($m[1]);
            if (file_exists($includePath)) {
                return $this->compile(file_get_contents($includePath));
            }
            return '';
        }, $content);

        // 5. Auth directives: @auth, @guest, @endauth, @endguest
        $content = preg_replace('/@auth/', '<?php if (auth()->check()): ?>', $content);
        $content = preg_replace('/@endauth/', '<?php endif; ?>', $content);
        $content = preg_replace('/@guest/', '<?php if (!auth()->check()): ?>', $content);
        $content = preg_replace('/@endguest/', '<?php endif; ?>', $content);

        // 6. Role directives: @role('management') ... @endrole
        $content = preg_replace_callback('/@role\([\'"]([^\'"]+)[\'"]\)/', function ($m) {
            return '<?php if (auth()->check() && auth()->role() === "' . $m[1] . '"): ?>';
        }, $content);
        $content = preg_replace('/@endrole/', '<?php endif; ?>', $content);

        // Raw PHP blocks: @php ... @endphp
        $content = preg_replace('/@php(.*?)@endphp/s', '<?php $1 ?>', $content);

        // 7. Flow control with balanced parenthesis parsing
        $content = $this->replaceBalancedDirective($content, 'if', '<?php if (%s): ?>');
        $content = $this->replaceBalancedDirective($content, 'elseif', '<?php elseif (%s): ?>');
        $content = $this->replaceBalancedDirective($content, 'foreach', '<?php foreach (%s): ?>');
        $content = $this->replaceBalancedDirective($content, 'for', '<?php for (%s): ?>');
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);

        // 8. Unescaped Output: {!! $var !!}
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?php echo $1; ?>', $content);

        // 9. Escaped Output: {{ $var }} (ensure Alpine.js @click / x-data isn't broken)
        $content = preg_replace('/(?<!@)\{\{\s*(.+?)\s*\}\}/s', '<?php echo htmlspecialchars((string)($1), ENT_QUOTES, "UTF-8"); ?>', $content);

        // 10. Escaped Blade tag @{{ something }} -> {{ something }}
        $content = preg_replace('/@\{\{\s*(.+?)\s*\}\}/s', '{{ $1 }}', $content);

        return $content;
    }

    protected function replaceBalancedDirective($content, $directive, $format)
    {
        $offset = 0;
        $needle = '@' . $directive . '(';
        while (($pos = strpos($content, $needle, $offset)) !== false) {
            $startParen = $pos + strlen($directive) + 1; // index of '('
            $depth = 1;
            $len = strlen($content);
            $i = $startParen + 1;
            while ($i < $len && $depth > 0) {
                if ($content[$i] === '(') {
                    $depth++;
                } elseif ($content[$i] === ')') {
                    $depth--;
                }
                $i++;
            }
            if ($depth === 0) {
                $expr = substr($content, $startParen + 1, $i - $startParen - 2);
                $replacement = sprintf($format, $expr);
                $content = substr_replace($content, $replacement, $pos, $i - $pos);
                $offset = $pos + strlen($replacement);
            } else {
                $offset = $startParen + 1;
            }
        }
        return $content;
    }
}
