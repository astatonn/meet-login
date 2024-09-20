<?php
function renderInput($name, $label = null, $props = array(), $classes = array(), $errors = array())
{
    $input = '';
    $prop = '';
    foreach ($props as $key => $value) {
        if ($value) {
            $prop .= sprintf('%s="%s"', $key, $value);
            $prop .= " ";
        }
    }

    if ($label) {
        $input.= sprintf("<label for='%s'>%s</label>", $name, $label);
    }


        $errorDiv = "<div id='".$name."_invalid_container' class='invalid-feedback text-center'>";
    if (count($errors) > 0) {
        array_push($classes, 'is-invalid');
        //foreach ($errors as $error) {
            $errorDiv .= join("<br>",$errors);
        //}
    }
    $errorDiv.= "</div>";
    $input.= sprintf("<input name='%s' class='%s' %s /> %s<br>", $name, join(" ", $classes), $prop, $errorDiv);

    echo $input ;
}

function getMemoryUsage()
{
    $size = \memory_get_usage(true);
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}