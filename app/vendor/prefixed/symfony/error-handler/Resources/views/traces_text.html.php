<table class="trace trace-as-text">
    <thead class="trace-head">
        <tr>
            <th class="sf-toggle" data-toggle-selector="#trace-text-<?php 
namespace Matomo\Dependencies;

echo $index;
?>" data-toggle-initial="<?php 
echo 1 === $index ? 'display' : '';
?>">
                <div class="trace-class">
                    <?php 
if ($numExceptions > 1) {
    ?>
                        <span class="text-muted">[<?php 
    echo $numExceptions - $index + 1;
    ?>/<?php 
    echo $numExceptions;
    ?>]</span>
                    <?php 
}
?>
                    <?php 
echo ($parts = \explode('\\', $exception['class'])) ? \end($parts) : '';
?>
                    <span class="icon icon-close"><?php 
echo $this->include('assets/images/icon-minus-square-o.svg');
?></span>
                    <span class="icon icon-open"><?php 
echo $this->include('assets/images/icon-plus-square-o.svg');
?></span>
                </div>
            </th>
        </tr>
    </thead>

    <tbody id="trace-text-<?php 
echo $index;
?>">
        <tr>
            <td>
                <?php 
if ($exception['trace']) {
    ?>
                <pre class="stacktrace">
<?php 
    echo $this->escape($exception['class']) . ":\n";
    if ($exception['message']) {
        echo $this->escape($exception['message']) . "\n";
    }
    foreach ($exception['trace'] as $trace) {
        echo "\n  ";
        if ($trace['function']) {
            echo $this->escape('at ' . $trace['class'] . $trace['type'] . $trace['function']) . '(' . (isset($trace['args']) ? $this->formatArgsAsText($trace['args']) : '') . ')';
        }
        if ($trace['file'] && $trace['line']) {
            echo ($trace['function'] ? "\n     (" : 'at ') . \strtr(\strip_tags($this->formatFile($trace['file'], $trace['line'])), [' at line ' . $trace['line'] => '']) . ':' . $trace['line'] . ($trace['function'] ? ')' : '');
        }
    }
    ?>
                </pre>
                <?php 
}
?>
            </td>
        </tr>
    </tbody>
</table>
<?php 
