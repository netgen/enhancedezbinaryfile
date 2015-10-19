{* DO NOT EDIT THIS FILE! Use an override template instead. *}
<div class="block">
<label>{'Max. file size'|i18n( 'design/standard/class/datatype' )}:</label>
<input type="text" name="ContentClass_enhancedezbinaryfile_max_filesize_{$class_attribute.id}" value="{$class_attribute.data_int1}" size="5" maxlength="5" />&nbsp;<span class="normal">MB</span>
<label>{'Allowed file types'|i18n( 'design/standard/class/datatype' )}:</label>
    {def $availableTypes = ezini('AllowedFileTypes', 'AllowedFileTypeList', 'module.ini')}

    {def $selectedFileTypes = array()}

    {if $class_attribute.data_text1|trim|length|gt(0)}
        {set $selectedFileTypes = $class_attribute.data_text1|explode('|')}
    {/if}

    <select multiple name="ContentClass_enhancedezbinaryfile_allowed_file_types_{$class_attribute.id}[]" >
        <option value="all_types" {if $selectedFileTypes|count|eq(0)}selected="selected"{/if}>Allow all types</option>
        {foreach $availableTypes as $availableType}
            <option value="{$availableType}" {if $selectedFileTypes|contains($availableType)}selected="selected"{/if}>{$availableType}</option>
        {/foreach}
    </select>

    {undef $selectedFileTypes $availableTypes}
</div>
