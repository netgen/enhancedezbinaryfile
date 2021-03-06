{* DO NOT EDIT THIS FILE! Use an override template instead. *}
{default attribute_base=ContentObjectAttribute}

{* Current file. *}
<div class="block">
<label>{'Current file'|i18n( 'design/standard/content/datatype' )}:</label>
{section show=$attribute.content}
<table class="list" cellspacing="0">
<tr>
<th>{'Filename'|i18n( 'design/standard/content/datatype' )}</th>
<th>{'MIME type'|i18n( 'design/standard/content/datatype' )}</th>
<th>{'Size'|i18n( 'design/standard/content/datatype' )}</th>
</tr>
<tr>
<td>{$attribute.content.original_filename|wash( xhtml )}</td>
<td>{$attribute.content.mime_type|wash( xhtml )}</td>
<td>{$attribute.content.filesize|si( byte )}</td>
</tr>
</table>
{section-else}
<p>{'There is no file.'|i18n( 'design/standard/content/datatype' )}</p>
{/section}

{section show=$attribute.content}
<input class="button" type="submit" name="CustomActionButton[{$attribute.id}_delete_binary]" value="{'Remove'|i18n( 'design/standard/content/datatype' )}" title="{'Remove the file from this draft.'|i18n( 'design/standard/content/datatype' )}" />
{section-else}
<input class="button-disabled" type="submit" name="CustomActionButton[{$attribute.id}_delete_binary]" value="{'Remove'|i18n( 'design/standard/content/datatype' )}" disabled="disabled" />
{/section}
</div>

{* New file. *}
<div class="block">
<label>{'New file for upload'|i18n( 'design/standard/content/datatype' )}:</label>
<input type="hidden" name="MAX_FILE_SIZE" value="{$attribute.contentclass_attribute.data_int1}000000"/>
<input class="box" name="{$attribute_base}_data_enhancedbinaryfilename_{$attribute.id}" type="file" />
</div>

<div class="block">
    <p>{'Allowed file types'|i18n( 'design/standard/content/datatype' )}:</p>
    {def $allowedFileTypes = $attribute.contentclass_attribute.data_text1|explode('|')}
    <table class="list" cellspacing="0">
        <tr>
            <th>{'File extension'|i18n( 'design/standard/content/datatype' )}</th>
            <th>{'MIME types'|i18n( 'design/standard/content/datatype' )}</th>

        </tr>
        {foreach $allowedFileTypes as $allowedFileType}
            {if ezini_hasvariable( $allowedFileType, 'Types', 'mime.ini' ) }
                {def $allowedFileTypeMimeTypesList = ezini( $allowedFileType, 'Types', 'mime.ini')|implode(', ')}
                <tr>
                    <td>{$allowedFileType|wash( xhtml )}</td>
                    <td>{$allowedFileTypeMimeTypesList|wash( xhtml )}</td>
                </tr>
                {undef $allowedFileTypeMimeTypesList}
            {/if}
        {/foreach}
    </table>
</div>

{/default}
