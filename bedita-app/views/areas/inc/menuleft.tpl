{*
Template incluso.
Menu a SX valido per tutte le pagine del controller.
*}

{assign var='method' value=$method|default:'index'}

<div class="primacolonna">
		
	<div class="modules"><label class="bedita" rel="{$html->url('/')}">BEdita 3.0</label></div>
		
	
	{if $module_modify eq '1'}
		<ul class="menuleft insidecol">
				<li {if $method eq 'viewArea'}class="on"{/if}>{$tr->link('New Publishing', '/areas/viewArea')}</li>
				<li {if $method eq 'viewSection'}class="on"{/if}>{$tr->link('New Section', '/areas/viewSection')}</li>
		</ul>
	{/if}
	
	<div class="insidecol publishingtree">	
			{if !empty($tree)}{$beTree->view($tree)}{/if}
	</div>

	<div style="margin-top:40px;">
	{include file="../common_inc/messages.tpl"}
	</div>
	
	{*
	<ul class="menuleft insidecol" style="border-top:5px solid gray; padding-top:10px;  margin-top:20px">
		<li {if $method eq 'hyper'}class="on"{/if}>{$tr->link('Publishing HyperTree', '/areas?hyper=1')}</li>	
	</ul>
	*}


</div>





