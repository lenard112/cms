<?php die("Access Denied"); ?>#x#a:2:{s:6:"output";s:0:"";s:6:"result";a:5:{i:0;O:8:"stdClass":3:{s:4:"link";s:84:"http://virtuemart.net/news/latest-news/462-security-release-of-vm2-6-10-and-vm2-9-9b";s:5:"title";s:41:"Security release of vm2.6.10 and vm2.9.9b";s:11:"description";s:7140:"<div><p>If you are using a version lower than 2.6.10, you should update right away.</p>
<p>During a routine audit done by the Sucuri firm, they found a critical vulnerability and informed the VirtueMart team.<br />The bug was immediately patched (in record time) and the <a href="http://virtuemart.net/index.php?option=com_content&amp;view=article&amp;id=54:downloads&amp;catid=162:uncategorised&amp;Itemid=100146#download">version 2.6.10 (stable version) and 2.9.9b (in RC state)</a> fixes this issue.</p>
<p>If you cannot update VirtueMart, please follow those <a href="http://virtuemart.net/#fix">instructions</a>.</p>
<h3>Our Security policy</h3>
<p>There were recently some misconceptions about our security policy. Some people complain that we are not following the "Full Disclosure" philosophy (please read <a href="http://en.wikipedia.org/wiki/Full_disclosure_(computer_security)">Full disclosure (computer security)</a>&nbsp;). The "Full Disclosure" comes from the beginning of the open source movement and is also to see as an answer to the "non-disclosure" behavior of proprietary software vendors. The experience was that sent vulnerabilities were not fixed. So the people learnt that revealing the vulnerability in public lead to a fast reaction of the blamed company. The evil guys of this business just started to blackmail companies. <br />There are of course also some other advantages. In case of Linux kernels, the idea is that all together work on a fix for it. The leaks are often a lot complexer and so the more people know about the faster it is fixed. Furthermore anyone should be able to learn from the leak to prevent the issue in future.</p>
<p>In our case, the most security leaks are fixed within minutes, maybe within 1-2 hours. So the argument, the more people the faster a fix is ready is not suitable for joomla/extensions. So we are following the philosophy of the "responsible disclosure" (please read <a href="http://en.wikipedia.org/wiki/Responsible_disclosure">Responsible disclosure</a>&nbsp;). Also <a href="http://blog.sucuri.net/2014/07/responsible-disclosure-sucuri-open-letter-to-mailpoet-and-future-disclosures.html">sucuri.net</a> is following this idea. They are professionals and know how to handle a vulnerability for the best of all users. They informed us secretly about the problem. We fixed it within a day, they tested our fix and asked if it is the right time to inform their customers. We did the most important thing, to provide a fix, only missing was the "responsible disclosure". So I agreed, but misunderstood them, because I did not meant that they disclosure the vulnerability in detail. A correct disclosure in our environment (php, opensource) must also always contain an explanation to fix the issue manually. The other reason is that the problem is actually in the joomla user "model" , and it should be also fixed in the JUser to prevent misuse of it before we should do the "Full disclosure". Persuading the joomla developers to protect their model got complexer than thought. Their argument is that there is no problem as long as you are using the Joomla Form. We got just stuck and must now prepare an explanation, why it is always bad to allow any form to override internal variables of an object.</p>
<h3><a name="fix"></a>How to get the security fix without updating VirtueMart</h3>
<p>If you cannot update VirtueMart, there are two possibilites:</p>
<h4>Exchange the file models/user.php</h4>
<p>The easiest way is just to exchange the user model with the new one:</p>
<ol>
<li>Dowload the latest version (<a href="http://dev.virtuemart.net/attachments/download/784/com_virtuemart.2.6.10.zip">VirtueMart 2.6.10</a> or <a href="http://dev.virtuemart.net/attachments/download/791/com_virtuemart.2.9.9b.zip">VirtueMart 2.9.9b</a>)</li>
<li>Replace the file&nbsp;<span>/administrator/components/com_virtuemart/models/user.php</span> with the new one.</li>
</ol>
<p>The user model is almost untouched for a year, so you should first try just to exchange the model.</p>
<h4>Patch the user.php file</h4>
<p>If you think your user model is too heavily modified, it is enough to add a unset($data['isRoot']); to the top of the user store function:</p>
<ol>
<li>Go to&nbsp;<span>/administrator/components/com_virtuemart/models/user.php</span></li>
<li>Search for the function named<span> function&nbsp;store(&amp;$data,$checkToken = TRUE)</span></li>
<li>Replace <span>if (!$user-&gt;bind($data)) { </span>with
<pre>if(!$user-&gt;authorise('core.admin','com_virtuemart')){
	$whiteDataToBind = array();
	$whiteDataToBind['name'] = $data['name'];
	$whiteDataToBind['username'] = $data['username'];
	$whiteDataToBind['email'] = $data['email'];
	if(isset($data['password'])) $whiteDataToBind['password'] = $data['password'];
	if(isset($data['password2'])) $whiteDataToBind['password2'] = $data['password2'];
	} else {
		$whiteDataToBind = $data;
	}
// Bind Joomla userdata
if (!$user-&gt;bind($whiteDataToBind)) {
		.....
</pre>
</li>
</ol>
<p>We just creating a new array and setting any variable manually (white list).</p>
<h3>The real problem behind all this</h3>
<p>The JUser model bind function just loops through the properties of the class and sets data with the same name to the object. The filtering is done by an attached JForm (Gui elements) to filter the input of the data. So if developers use the joomla model without form, they have to filter the data themself, else it is possible to override internal variables of the object. <br />The binding for normal JTables does not override internal variables as long you follow the habit/convention to name them with a trailing underscore _. The check function additionally ensures that the data is correct. But the juser object does not follow the own joomla habits. Additionally it is very unclean to use MVC and to have a model, which needs GUI elements to do correct filtering. There exists enough tasks to use a model without any GUI. For a developer just using the joomla API it is like a trap. A model should be secure by itself, without the need of a "View" or "Controller" to be safe. SCNR, but joomla 2.5.16 fixed a security leak in some the JFormFields. So other solutions based on that were also very unsecure for years.</p>
<p>The suggested fix in the joomla user model is very easy. Just unset the sensitive data, if a user is not admin. This should be done in the bind function and in the store function. The advantage lays on the hand. <br />A lot other extensions for joomla become more secure. It is very unlikely that only VM has this problem.<br />People can do a small joomla update and still use their modified extensions.</p>
<p>Personally I see the request for full disclosure as a typical academic, but noobish request. Not only the good guys learn from disclosures. The black hat fraction also learns from it. It is important to differ and sometimes a full disclosure makes absolut sense, but not always. It depends on the complexity of the problem, how many people already know about, the reaction of the maintainers, and so on.</p>
<p>&nbsp;</p></div>";}i:1;O:8:"stdClass":3:{s:4:"link";s:75:"http://virtuemart.net/news/latest-news/461-virtuemart-2-6-8-includes-realex";s:5:"title";s:32:"VirtueMart 2.6.8 includes Realex";s:11:"description";s:4148:"<div><p>We are pleased to announce that Realex is now available through VirtueMart’s ecommerce solution.</p>
<div><img src="http://virtuemart.net/images/virtuemart/news/realex.png" alt="VirtueMart 2.6.8 includes Realex" class="align-left" />
<div>
<h3><a href="http://www.realexpayments.com/partner-referral?id=virtuemart">Sign Up today</a></h3>
<p>and receive 1 month free processing!</p>
</div>
</div>
<p>Use the Realex Payments integration as part of your VirtueMart e-commerce solution and benefit from a seamless, no-hassle integration offering industry-leading features and support.</p>
<h3>Why Choose Realex?</h3>
<p>Realex Payments is a leading European payment services provider, with offices in Dublin, London and Paris. We currently process in excess of €24 billion annually for over 12,500 clients including Virgin Atlantic, notonthehighstreet.com, Vodafone, Paddy Power and BooHoo.</p>
<p>Some of the key reasons merchants choose us over other gateways:</p>
<ul>
<li><strong>3DSecure - Protect yourself</strong> against fraud and chargebacks. We fully support 3DSecure, which provides additional protection should a chargeback occur.</li>
<li><strong>Access your funds quickly</strong> - The Acquiring Banks we work with typically settle funds into your account within 2 days, unlike 7 days for some of our competitors.</li>
<li><strong>Pricing - As you scale your business</strong>, other payment processors can very quickly become expensive. We offer a flat per transaction rate that can be tailored to your business as you grow.</li>
<li><strong>Customer Service - We don’t believe in IVRs</strong>, simply pick up the phone and speak with a familiar voice</li>
</ul>
<h3>Realex Features</h3>
<p>"<strong>Realex Payments are delighted to have partnered with the VirtueMart core team to build a simple to use and feature-rich integration.</strong>"</p>
<p>Features include:</p>
<ul>
<li>Processing for all card payment types</li>
<li>Major alternative payment methods (PayPal, Sofort, GiroPay, ELV, iDeal)</li>
<li>Transactions processing in 150 currencies</li>
<li>Fully PCI level 1-compliant, responsive and customisable hosted payment page</li>
<li>1-Click checkout for a seamless checkout experience</li>
<li>Secure Card Tokenisation for recurring payments - RealVault</li>
<li>Dynamic Currency Conversion to allow shoppers to pay in their currency</li>
<li>Fraud checks: CVN, 3DSecure (incl. Amex SafeKey) and AVS</li>
<li>Comprehensive suite of fraud management tools - RealScore</li>
<li>Delayed/Deferred Settlement</li>
<li>Comprehensive Order Management (refund, void, settle) from the VirtueMart back-office</li>
<li>Plug and play access to our APIs</li>
<li>Comprehensive, configurable and flexible transaction routing capability</li>
</ul>
<h3>To Find Out More</h3>
<p>For more information please contact us on <a href="mailto:sales@realexpayments.com">sales@realexpayments.com</a> or <a href="http://www.realexpayments.com/partner-referral?id=virtuemart">Sign up</a> with Realex Payments today to get one month free processing and join the hundreds of VirtueMart merchants who know and trust us to process thousands of orders per week.</p>
<h3>Updates and bug fixes VirtueMart 2.6.8</h3>
<ul>
<li>Preventing double orders (3rd party developers may adjust their payments)</li>
<li>Shipment price display in product details</li>
<li>Better Itemid handling in the router</li>
<li>Thumbnail resizing if one dimension is 0 (same as already for vm3)</li>
<li>Router is using category model now, better use of already cached data</li>
<li>If <em>One Page Checkout</em> is disabled and <em>Show checkout steps</em> is activated, then the shipment and payment selection is only shown if a shipment/payment is already selected. So this give back the old VM1 behaviour</li>
<li>Little fix for shipment/payment tax with different VatTax rules</li>
<li>Fix for product cache (happened rare)</li>
<li>Lot small fixed typos, increased robustness, little enhancements</li>
</ul>
<p>&nbsp;</p>
<div>
<p><a href="http://virtuemart.net//downloads">DOWNLOAD NOW</a></p>
</div></div>";}i:2;O:8:"stdClass":3:{s:4:"link";s:88:"http://virtuemart.net/news/latest-news/460-updates-about-virtuemart-3-support-membership";s:5:"title";s:46:"Updates about VirtueMart 3, Support Membership";s:11:"description";s:6418:"<div><h2>VirtueMart 3, Core is ready for testing</h2>
<p>We finally can announce that the VirtueMart 3 core is ready as <a href="http://dev.virtuemart.net/attachments/download/759/com_virtuemart.2.9.8_extract_first.zip" target="_blank"><em>Release Candidate 2.9.8</em></a>. Now the remaining job is to test the core intensively on joomla 3.3 and to add missing backward compatibility for easy updating. As far we can see all API changes are done.</p>
<p>The primary task is now to test the plugins, adjust them to the new joomla 2.5 style and if necessary add fallbacks or provide developer information for switches in our <a href="http://docs.virtuemart.net/tutorials/development/175-code-adjustments-for-virtuemart-3.html">Code adjustments for Virtuemart 3</a>. This manual will grow, the more developers provide feedback, the faster. The plugins for the customfields must be updated. All extensions working with the customs need to be updated. Except for the plugins for the customfields, the old plugins will almost directly work. The xml files must be updated to j2.5 style. They need some adjustments&nbsp;anyway to run with Joomla 3 like using vRequest (respectivly JInput).</p>
<h3>The changes in VirtueMart 3</h3>
<p>Our priority for VM3 is to develop a robust core providing a cleaner structure and less code. We reduced the dependencies on joomla, but increased on the other hand the integration. For example, the core now uses only the JFormFields of joomla 2.5 and not any longer the old vmParameter, but we added vRequest (MIT) as choice for JInput. Developers can now use the normal JFormField joomla conventions for all plugins.</p>
<p>You can re-use your layouts by using the new sublayouts (like minilayouts). They give your store a consistent appearance and make it easier to adjust standards for different layouts in one overridable file. The input data is very unified which makes it stable against updates.</p>
<p>The new core has an advanced cart with enhancements to provide better update compatibility. For example the new custom userfields now include an option to be displayed on the checkout page and can use their own overridable mini layouts making it easy to adjust the cart to legal requirements without touching the template. The data stored in the session is minified and therefore the cart now uses normal products, which can be easily modified by plugins (for example to adjust the weight).</p>
<p>The new jQuery versions are now mainly the same as in joomla 3.3 (jQuery v1.11.0,jQuery UI - v1.9.2, legacy complete). Shops using joomla 2.5 with VM3 will also benefit from this. It will prevent needless configuration problems.</p>
<p>Frontend Editing combined with the joomla ACL now allows your vendors to directly access the VirtueMart backend from the frontend, without having real access to the joomla backend. This feature is still under heavily development and we are still looking for funds to complete it. So far vendors can just create new products, edit their products and list their products. It is the first step to make multivendor accessible for normal endusers.</p>
<p>"<span title="If a user is only in a additional shoppergroup, he will become also automatically the default shoppergroup.">Additional Shoppergroup" is a new feature for shoppergroups, which do not replace the default groups.</span>
</p>
<p>New internal program caches reduce the sql queries for the most used tasks by more than 20%. &nbsp;</p>
<p>and of course the new customfields. With new options, redesigned and a lot more flexible to use.</p>
<h2>Planned</h2>
<p>A new trigger system, only for the checkout is started. It needs a new derived function/trigger and cannot be done with the old triggers. It will work with some kind of event system and call the proper plugins directly. We will write this after the first release. Old plugins then just need to be updated with the new trigger to participate in the new system.</p>
<p>Simple ajax reloading of component view. We are very happy that Max Galt, the developer of the cherry picker has donated his javascript code for dynamic reloading of products to the VirtueMart Project</p>
<p>Please download and test&nbsp; <br /><a href="http://dev.virtuemart.net/attachments/download/761/com_virtuemart.2.9.8a_extract_first.zip">com_virtuemart.2.9.8a_extract_first.zip</a>
</p>
<p><a href="http://dev.virtuemart.net/attachments/download/760/VirtueMart2.9.8_Joomla_2.5.22-Stable-Full_Package.zip">VirtueMart2.9.8_Joomla_2.5.22-Stable-Full_Package.zip</a>
</p>
<h2>VirtueMart Support Membership</h2>
<p>We have successfully introduced a membership for the VirtueMart Project recently. We recognized that VirtueMart users want a safe support address if they find a bug and that a public forum is not adequate for serious business owners. In the past two years the core development team also had to spend too much time to provide customizations to make their living. Providing a really good maintained and professionally tested core takes more and more time and the complexity required to keep it simple for endusers and web agencies is increasing with every version.</p>
<ul>
	<li>VirtueMart continues with one free version</li>
	<li>Members are customers with access to our ticket system</li>
	<li>The membership helps the core developers to focus on the project and enables us to provide a very high code quality</li>
	<li>Any tier gives a vote for a desired feature to influence the roadmap</li>
	<li>Added value (multi-add layout, display shipment costs for products,...)</li>
</ul>
<p>There are also some nice ideas to enhance the core.&nbsp;For example multi-image upload, different sizes for images, more different layouts to choose from, angular js (very fast), more multivendor, multi-language tools, enhanced js for the BE, flexible and configurable OPC, ...</p>
<p>We also already invested into the <a href="https://www.indiegogo.com/projects/advance-the-joomla-url-router">new router of Hannes Papenberg</a> and it will be provided to the VirtueMart Support Members.</p>
<p><a href="http://extensions.virtuemart.net/support/virtuemart-supporter-membership-detail">Become a VirtueMart Associate Member</a>
</p>
<p>There is also already a thread about this in the forum&nbsp;<a href="http://forum.virtuemart.net/index.php?topic=124355.0">http://forum.virtuemart.net/index.php?topic=124355.0</a>
</p></div>";}i:3;O:8:"stdClass":3:{s:4:"link";s:75:"http://virtuemart.net/news/latest-news/459-virtuemart-2-6-6-includes-paybox";s:5:"title";s:32:"VirtueMart 2.6.6 includes Paybox";s:11:"description";s:2342:"<div><p>We are pleased to announce that Paybox is now available through VirtueMart’s ecommerce solution.</p>
<p><strong><img src="http://virtuemart.boutique-paybox.com/PayboxLogo.jpg" width="200" alt="Paybox" style="margin: 5px auto; display: block;" /></strong></p>
<p>PAYBOX, the secured payment platform of&nbsp;<a href="http://www1.paybox.com/group-presentation/?lang=en"><span>Point-VeriFone Group,</span></a>&nbsp;offers a range of solutions and services to the e-merchants to manage their settlements, and this for all the sales channel.</p>
<p>Paybox currently processes payment flows for over 27,000 merchants and 120 million transactions per year.<br /> Paybox operates a payment service in conjunction with various actors in the e-commerce industry.</p>
<p>15 YEARS’ EXPERIENCE OF WORKING WITH A WIDE AND VARIED CUSTOMER BASE.</p>
<p>Paybox has been supporting online retailers in their day-to-day business for over 15 years, and offers a secure, flexible and turnkey payment solution that meets all your requirements thanks to its unique platform -&nbsp; cross-channel, multiple payment methods, multi-services, multi-bank, multi-currency - and its fraud management and reporting tools.</p>
<p>A wide range of technical environments enables Paybox to adapt its solutions to all types of project, from the most simple to the most complex whatever the size of your business, your sales channels or your business sector.</p>
<p>Paybox is certified and recognized by all banks (PCI/DSS 2.0 operator, 3-D Secure activated with all banks).</p>
<p>BY BEING PART OF POINT GROUP / VERIFONE, PAYBOX IS DEVELOPING ITS POSITION IN EUROPE AND INTERNATIONALLY.</p>
<p>The Paybox platform accepts 52 currencies, allowing you to cash your transactions anywhere in the world, either via your distance sale contract or via your connection contract with buyers and international payment methods.</p>
<h3>Updates and Bug Fixes</h3>
<ul>
<li>fix preventing creation of doubled orders</li>
<li>use for token check in updatesmigration.php the new vmCheckToken of vRequest</li>
<li>update of heidelpay payment plugin</li>
<li>fixed creation of slugs for "adding a child"</li>
<li>fixes while updating the tables for the Joomla updater</li>
</ul>
<div>
<p><a href="http://virtuemart.net/downloads">DOWNLOAD NOW</a></p>
</div></div>";}i:4;O:8:"stdClass":3:{s:4:"link";s:71:"http://virtuemart.net/news/latest-news/458-new-release-virtuemart-2-6-2";s:5:"title";s:28:"New Release VirtueMart 2.6.4";s:11:"description";s:976:"<div><p>VirtueMart release pure bugfix release VM2.6.4</p>
<p>This is a pure bug release. The liveupdater did not work, so we replaced it by the joomla updater. We also checked the language loaders which should work now more robust. The cache returns the JTable object again.&nbsp;</p>
<p>List of bugfixes:</p>
<ul>
	<li>Important Fix for vmtable. Cache gave back a standard object with the data. But before it was a JObject. The data is stored as standard object and bind to the table which is returned.</li>
	<li>Reworked loading of language files in email and invoice</li>
	<li>Correct language loader for plugins added</li>
	<li>Small fix for loadJLang (reset of tested path)</li>
	<li>Loading Be language, changed to FE language</li>
	<li>Added replyto the shopper in vendor email</li>
	<li>Akeeba liveupdater removed</li>
	<li>xml for joomla updater added</li>
</ul>
<div>
	<p><a href="http://virtuemart.net/downloads">DOWNLOAD NOW</a>
	</p>
</div></div>";}}}