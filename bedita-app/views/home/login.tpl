{$html->script("form", false)}
{$html->script("libs/jquery/plugins/jquery.form", false)}
{$html->script("libs/jquery/plugins/jquery.metadata", false)}

<div class="primacolonna">
	 <div class="modules"><label class="bedita" rel="{$html->url('/')}">{$conf->projectName|default:$conf->userVersion}</label></div>
	<div class="insidecol colophon">	
		{$view->element('colophon')}
	    {$conf->colophon|default:''}
	</div>
</div>


<div class="secondacolonna">
	<div class="modules">
	   <label class="admin">{t}Login{/t}</label>
	</div> 
</div>


<div class="login">
	<form action="{$html->url('/authentications/login')}" method="post" name="loginForm" id="loginForm" class="cmxform">
	<fieldset>
		<input type="hidden" name="data[login][URLOK]" value="{$beurl->here()}" id="loginURLOK" />
		
		<label class="block" id="luserid" for="userid">{t}Username{/t}</label>
		<input class="big" tabindex="1" type="text" name="data[login][userid]" id="userid" class="{literal}{ required:true}{/literal}" title="{t}Username is required{/t}"/>
		<label class="block" id="lpasswd" for="passwd">{t}Password{/t}</label>
		<input class="big" tabindex="2" type="password" name="data[login][passwd]" id="passwd" class="{literal}{ required:true}{/literal}" title="{t}Password is required{/t}"/>
		
		<input class="bemaincommands" tabindex="2" type="submit" value="{t}Enter{/t}"/>
	</fieldset>
	</form>

	<label class="block"><a href='javascript:void(0)' onClick="$('#pswforget').toggle('fast')">{t}Forgot your username or password?{/t}</a></label>
	
	<div id="pswforget" style="margin-top:20px; display:none">
		<form method="post" action="{$html->url('/authentications/recoverPassword')}">
		<input class="big" style="width:280px" type="text" placeholder="{t}Write your email here{/t}" name="data[email]"/>
		<input class="bemaincommands" type="submit" value="{t}Send{/t}"/>
		{if isset($conf->projectAdmin)}
		{t}or{/t} <label><a href="mailto:{$conf->projectAdmin}">{t}contact the project admin{/t}</a></label>{/if}
	</div>
</div>
