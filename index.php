<!DOCTYPE html>
<html>
	<head>
		<title>WebSocket testing</title>
		<link rel="stylesheet" href="css/base.css"/>
	</head>
	<body>
		<?php
		//jquery is included in this demo but this is not dependent on jquery i just think its the easiest dom manipulation lib out there
		?>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

		<script>
			var soc;
			var log = function(msg){
				var l = document.getElementById('log');
				var d = document.createElement("div");
				d.appendChild(document.createTextNode(msg));
				l.appendChild(d);
			};

			var scanWebSocket = function(){
				for(var i in WebSocket){
					if(WebSocket.hasOwnProperty(i)){
						log(i);
					}
				};
			};

			var getChar = function(len){
				chr = String.fromCharCode(Math.floor((Math.random()*100)%(122-97)+97));
				
				if(len){
					chr += getChar(--len);
				}

				return chr;
			};


		 	var connectSocket = function(){
				var soc = new WebSocket('ws://'+window.location.host+':10000/');
				log('connect called');
				soc.onopen = function(){
					log('soc onopen');
				};

				soc.onerror = function(evt){
					log('onerror');
				}

				soc.onmessage = function(evt){
					log('onmessage '+evt.data);
				};

				soc.onclose = function(){
					log('socket closed');
				}

				return soc;
			}
			/*
			var mathSocket = {
				init:function(){
					$("#math-form").bind('submit',function(){
						var val = $(this).find("input[name=math]").val();
						if(val.length){
							soc.send(val);
						} else {
							$(this).find('span').html("");
						}
						return false;
					});

					soc.onmessage = function(evt){
						log('message'+evt);
						console.log(evt);

						try{
							var data = JSON.parse(evt.data);
						}catch(e){
							alert('json parse exception getting message response');
						}

						if(data.data){
							$("#math-form").find('span').html(data.data);
							log('on message'+evt.data);
						}
					};
				}
			}*/


			var chatSocket = {
				soc:false,
				init:function(){
					$.each(this,function(k,v){
						if(v.init){
							v.init();
						}

					});
					this.soc = connectSocket();

					var z = this;
					this.soc.onmessage = function(evt){
						try{
							var data = JSON.parse(evt.data);
						}catch(e){
							alert('JSON.parse exception: "'+e.message+'" on "'+evt.data+'"');
						}
						if(data && data.data){
							z.handle_message(data);
						}
					};

				},
				handle_message:function(data){
					switch(data.event){
						case "chat":
							this.messages.add(data.data);
							break;
						case "userlist":
							this.userlist.update(data);
							break;
						case "auth":
							this.userlist.set_id(data);
							break;
						default:
							alert('unknown message event: '+data.event);
					}
				},
				userlist:{
					init:function(){
						var z = this;
						$("#name-form").bind('submit',function(){
							var val = $(this).find("input[type=text]").val().replace(/[^a-zA-z0-9-._]/,'');
							if(val.length){
								z.set_name(val);
								$(this).find("input[type=text]").val(val);
							} else {
								alert('empty or invlaid characters in name');
							}
							return false;
						});
					},
					id:0,
					set_id:function(data){
						if(data.client){
							this.id = data.client;
						}
					},
					set_name:function(name){
						chatSocket.soc.send(JSON.stringify({cmd:'name',name:name}));
					},
					update:function(data){
						var z = this;
						$(".chat-user-list .chat-user").remove();

						if(data.client){
							z.id = data.client;
						}

						$.each(data.data,function(k,v){
							var name = k;
							if(v && v.name){
								name = v.name;
							}
							if(k  == z.id){
								name = '<b>ME!</b> '+name;
							}
							z.add(name);
						});
					},
					add:function(name){
						$("<div class='chat-user' data-name='"+name+"'><a href='javascript://'>"+name+"</a></div>").appendTo(".chat-user-list");
					},
					remove:function(name){
						$(".chat-user[data-name="+name+"]").remove();
					}
				},
				messages:{
					limit:30,
					init:function(){
						var z = this;
						$("#chat-form").bind('submit',function(){
							try{
								var input = $(this).find("input[type=text]")[0];
								var val = $(input).val();
								if(val.length){
									z.send(val);
									$(input).val("");
								}
							}catch(e){
								alert('exception: '+e.message);
							}
							return false;
						});
					},
					add:function(data){

						$(".chat-messages").append("<div class='message'>["+data.date+"]"+data.client+": "+data.message+"</div>");

						this.enforceLimit();
						$(".chat-messages").each(function(){
							this.scrollTop = $(this).height();
						});
					},
					enforceLimit:function(){
						$(".chat-messages").each(function(){
							var len = $(this).find(".messages").length;
							if(len > this.limit){
								var remove = len-this.limit
								$(this).find(".messages").each(function(){
									if(!remove) return false;
									$(this).remove();
									remove--;
								});
							}
						});
					},
					send:function(data){
						chatSocket.soc.send(JSON.stringify({cmd:'chat',data:data}));
					}
				}
			};

			$(function(){
				chatSocket.init();
			});
		</script>
		<h1>
			WebSocket testing
		</h1>

		<div>
			<strong>solumWebSocket</strong>
			<br/>git hub: <a href='http://github.com/soldair/solumWebSocket'>http://github.com/soldair/solumWebSocket</a>
		</div>

		<h2>Chat!</h2>
		<div class="clearfix">
			<div style="width:200px;border:2px inset #999;float:left;min-height:300px;">
				<span style="text-decoration:underline;">logged in users</span>
				<form action="#" id="name-form">
					name:<input type="text" value=""/>
					<input type="submit" value="update name"/>
				</form>
				<div class="chat-user-list"></div>
			</div>
			<div style="width:500px;border:2px inset purple;float:left;height:300px;overflow:scroll;" class="chat-messages"></div>
		</div>
		<div>
			<form id="chat-form" action="#">
			chat:<input type="text" value="" style="width:600px;"/><input type="submit" value="send"/>
			</form>
		</div>

		<?php
		/*
		<form id="math-form" action="#">
			<div><input type="text" value="" name="math"/> = <span id="math-result"></span></div>
			<input type="submit" value="submit"/>
		</form>
		*/?>
		<div id='log' style="margin:5px;padding:5px;background-color:#000;color:#fff;font-weight:bold;">

		</div>
		<div style="margin:5px;border:2px inset #999;">
			README!
			<pre><?php
				echo file_get_contents('README');
			?></pre>
		</div>
	</body>
</html>
