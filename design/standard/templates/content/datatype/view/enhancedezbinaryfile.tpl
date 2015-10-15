{if $attribute.content}
<a href={concat( 'content/download/', $attribute.contentobject_id, '/', $attribute.id,'/version/', $attribute.version , '/file/', $attribute.content.original_filename|urlencode )|ezurl}>{$attribute.content.original_filename|wash( xhtml )}</a>&nbsp;({$attribute.content.filesize|si( byte )})
{/if}