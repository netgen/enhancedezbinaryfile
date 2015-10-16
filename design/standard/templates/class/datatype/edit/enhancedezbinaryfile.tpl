{* DO NOT EDIT THIS FILE! Use an override template instead. *}
<div class="block">
<label>{'Max. file size'|i18n( 'design/standard/class/datatype' )}:</label>
<input type="text" name="ContentClass_enhancedezbinaryfile_max_filesize_{$class_attribute.id}" value="{$class_attribute.data_int1}" size="5" maxlength="5" />&nbsp;<span class="normal">MB</span>

<label>{'Allowed file types'|i18n( 'design/standard/class/datatype' )}:</label>
    {def $availableTypes = ezini('AllowedFileTypes', 'AllowedFileTypeList', 'module.ini')}
    <select multiple name="ContentClass_enhancedezbinaryfile_allowed_file_types_{$class_attribute.id}[]" >
        {foreach $availableTypes as $availableType}
            <option value="{$availableType}">{$availableType}</option>
        {/foreach}
    </select>
</div>
