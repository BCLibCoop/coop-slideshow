/**
*	Shim to adjust vertical aspect of text-only slides in bxslider
*	to fit the 1000x300 aspect ratio at various widths.
*
**/(function(e,t){var n,r=function(){n=this;return n.init()};r.prototype={init:function(){return n},onload:function(t){var n=e(".bx-viewport").width(),r=parseInt(.3*n)+"px";e(".slide.text").css("height",r);e(".bx-wrapper").css("height",r);e(".bx-viewport").css("height",r);var i=e(".alpha-pager.vertical").css("display");e(".alpha-pager.vertical").css("display","none");e(".alpha-pager.vertical").css("top","-"+r);e(".alpha-pager.vertical").css("height",r);e(".alpha-pager.vertical").css("display",i)},reset:function(t,n,r){if(t.hasClass("text")){var i=parseInt(.3*t.width())+"px";t.css("height",i);e(".bx-wrapper").css("height",i)}}};e.fn.text_shim=function(){return new r}})(jQuery,window);jQuery().ready(function(e){window.coop_slider=e().text_shim()});