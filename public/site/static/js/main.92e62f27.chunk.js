(this.webpackJsonpnuppin=this.webpackJsonpnuppin||[]).push([[0],{30:function(e,n,t){e.exports=t(42)},42:function(e,n,t){"use strict";t.r(n);var a=t(13),r=t(1),i=t.n(r),o=t(25),c=(t(35),t(6)),d=t(2);function l(){var e=Object(c.a)(["\n  html, body {\n  font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;\n  -webkit-font-smoothing: antialiased;\n  -moz-osx-font-smoothing: grayscale;\n  background:",";\n  color: #333333;\n  font-size: 16px;\n  z-index: 1;\n  width: 100%;\n  height: 100%;\n  max-width: 1600px;\n  margin: 0 auto;\n  a{\n      outline: none;\n  }\n}"]);return l=function(){return e},e}var m=document.location.host.split(".")[1]?document.location.host.split(".")[1]:document.location.host,p=Object(d.b)(l(),"parceiro"===m?"#000":"#fff"),u=t(10),s=t(4),g=t(19),h=t(3),f=d.c.div.withConfig({displayName:"accordion__Container",componentId:"sc-1ovpdw0-0"})(["display:flex;border-bottom:8px solid ",";background:",";"],(function(e){return e.theme.faq_border}),(function(e){return e.theme.feature_background})),x=d.c.div.withConfig({displayName:"accordion__Frame",componentId:"sc-1ovpdw0-1"})(["margin-bottom:40px;"]),b=d.c.div.withConfig({displayName:"accordion__Inner",componentId:"sc-1ovpdw0-2"})(["display:flex;padding:70px 45px;flex-direction:column;width:815px;margin:auto;"]),w=d.c.h1.withConfig({displayName:"accordion__Title",componentId:"sc-1ovpdw0-3"})(["font-size:50px;line-height:1.1;margin-top:0;margin-bottom:8px;color:",";text-align:center;@media (max-width:600px){font-size:35px;}"],(function(e){return e.theme.feature_color})),v=d.c.div.withConfig({displayName:"accordion__Item",componentId:"sc-1ovpdw0-4"})(["color:white;margin:10px auto;max-width:815px;width:100%;&:first-of-type{margin-top:3em;}"]),y=d.c.div.withConfig({displayName:"accordion__Header",componentId:"sc-1ovpdw0-5"})(["display:flex;justify-content:space-between;cursor:pointer;margin-bottom:1px;font-size:26px;font-weight:normal;background:",";padding:0.8em 1.2em 0.8em 1.2em;user-select:none;align-items:center;img{filter:brightness(0) invert(1);width:24px;@media (max-width:600px){width:16px;}}@media (max-width:600px){font-size:16px;}"],(function(e){return e.theme.faq_item})),E=d.c.div.withConfig({displayName:"accordion__Body",componentId:"sc-1ovpdw0-6"})(["max-height:1200px;transition:max-height 0.25s cubic-bezier(0.5,0,0.1,1);font-size:24px;font-weight:normal;line-height:normal;background:",";padding:0.8em 2.2em 0.8em 1.2em;white-space:pre-wrap;user-select:none;@media (max-width:600px){font-size:16px;line-height:22px;}"],(function(e){return e.theme.faq_item})),_=t(17),j=t(18),k=Object(r.createContext)();function O(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(f,t,i.a.createElement(b,null,n))}O.Title=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(w,t,n)},O.Frame=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(x,t,n)},O.Item=function(e){var n=e.children,t=Object(h.a)(e,["children"]),a=Object(r.useState)(!1),o=Object(g.a)(a,2),c=o[0],d=o[1];return i.a.createElement(k.Provider,{value:{toggleShow:c,setToggleShow:d}},i.a.createElement(v,t,n))},O.Header=function(e){var n=e.children,t=Object(h.a)(e,["children"]),a=Object(r.useContext)(k),o=a.toggleShow,c=a.setToggleShow;return i.a.createElement(y,Object.assign({onClick:function(){return c(!o)}},t),n,o?i.a.createElement(_.a,null):i.a.createElement(j.a,null))},O.Body=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return Object(r.useContext)(k).toggleShow?i.a.createElement(E,t,n):null};var C=d.c.p.withConfig({displayName:"card__Title",componentId:"sc-1y18ltg-0"})(["font-size:24px;color:#000;font-weight:bold;margin:10px 0 0 30px;"]),z=d.c.div.withConfig({displayName:"card__Container",componentId:"sc-1y18ltg-1"})(["display:flex;flex-direction:column;"]),I=d.c.div.withConfig({displayName:"card__Group",componentId:"sc-1y18ltg-2"})(["max-width:1140px;margin:20px auto;box-shadow:0 3px 10px rgba(0,0,0,0.2);border-radius:5px;width:100%;@media (min-width:700px){padding:50px;}@media (max-width:1160px){max-width:920px;padding:40px;}@media (max-width:940px){max-width:700px;padding:30px;}@media (max-width:720px){max-width:480px;padding:20px;}@media (max-width:500px){max-width:260px;padding:10px;}"]),N=d.c.div.withConfig({displayName:"card__Entities",componentId:"sc-1y18ltg-3"})(["display:flex;flex-direction:row;flex-wrap:wrap;padding:20px;"]),T=d.c.p.withConfig({displayName:"card__SubTitle",componentId:"sc-1y18ltg-4"})(["font-size:12px;color:#000;font-weight:bold;margin-top:0;margin-bottom:0;user-select:none;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.5em;min-height:3em;"]),q=Object(d.c)(T).withConfig({displayName:"card__Price",componentId:"sc-1y18ltg-5"})(["color:green;margin-top:10px;line-height:normal;min-height:auto;"]),S=d.c.p.withConfig({displayName:"card__Text",componentId:"sc-1y18ltg-6"})(["margin-top:5px;font-size:10px;color:#000;margin-bottom:0;user-select:none;line-height:normal;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.5em;min-height:3em;"]),P=d.c.div.withConfig({displayName:"card__Meta",componentId:"sc-1y18ltg-7"})(["padding:10px;"]),F=d.c.img.withConfig({displayName:"card__Image",componentId:"sc-1y18ltg-8"})(["border:0;max-width:100%;height:200px;cursor:pointer;padding:0;margin:0;border-radius:5px;"]),B=d.c.div.withConfig({displayName:"card__Item",componentId:"sc-1y18ltg-9"})(["box-shadow:0 3px 10px rgba(0,0,0,0.2);background:white;display:flex;width:200px;flex-direction:column;margin:10px;border-radius:5px;position:relative;cursor:pointer;transition:transform 0.2s;&:hover{transform:scale(1.04);}"]);function L(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(z,t,n)}L.Group=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(I,t,n)},L.Title=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(C,t,n)},L.SubTitle=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(T,t,n)},L.Price=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(q,t,n)},L.Text=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(S,t,n)},L.Entities=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(N,t,n)},L.Meta=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(P,t,n)},L.Item=function(e){e.item;var n=e.children,t=Object(h.a)(e,["item","children"]);return i.a.createElement(B,t,n)},L.Image=function(e){var n=Object.assign({},e);return i.a.createElement(F,n)};var D=d.c.div.withConfig({displayName:"jumbotron__Inner",componentId:"z17ftq-0"})(["display:flex;align-items:center;justify-content:space-between;flex-direction:",";max-width:1600px;margin:auto;width:100%;@media (max-width:1000px){flex-direction:column;}@media (min-width:1000px){min-height:100vh;}"],(function(e){return e.direction})),A=d.c.div.withConfig({displayName:"jumbotron__Pane",componentId:"z17ftq-1"})(["width:50%;display:flex;flex-direction:column;@media (max-width:1000px){width:100%;padding:0 45px;text-align:center;}"]),R=d.c.h1.withConfig({displayName:"jumbotron__Title",componentId:"z17ftq-2"})(["font-size:50px;line-height:1.1;margin-bottom:8px;@media (max-width:600px){font-size:35px;}"]),G=d.c.h2.withConfig({displayName:"jumbotron__SubTitle",componentId:"z17ftq-3"})(["font-size:26px;font-weight:normal;line-height:normal;@media (max-width:600px){font-size:18px;}@media (min-width:1000px){width:80%;}"]),H=d.c.img.withConfig({displayName:"jumbotron__Image",componentId:"z17ftq-4"})(["max-width:100%;height:auto;align-self:center;"]),M=d.c.div.withConfig({displayName:"jumbotron__Item",componentId:"z17ftq-5"})(["display:flex;border-bottom:8px solid ",";padding:50px 5%;color:white;overflow:hidden;"],(function(e){return e.theme.secondary_color})),V=d.c.div.withConfig({displayName:"jumbotron__Container",componentId:"z17ftq-6"})(["background:",";@media (max-width:1000px){",":last-of-type h2{margin-bottom:50px;}}"],(function(e){return e.background}),M);function Q(e){var n=e.children,t=e.direction,a=void 0===t?"row":t,r=Object(h.a)(e,["children","direction"]);return i.a.createElement(M,r,i.a.createElement(D,{direction:a},n))}Q.Container=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(V,t,n)},Q.Pane=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(A,t,n)},Q.Title=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(R,t,n)},Q.SubTitle=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(G,t,n)},Q.Image=function(e){var n=Object.assign({},e);return i.a.createElement(H,n)};d.c.div.withConfig({displayName:"wrapper__Container",componentId:"sc-13h7cpv-0"})(["min-height:100vh;display:grid;grid-template-rows:auto 1fr auto;grid-template-columns:100%;"]);var J=d.c.div.withConfig({displayName:"opt-form__Container",componentId:"sc-12qpfwk-0"})(["display:flex;justify-content:center;height:100%;margin-top:20px;flex-wrap:wrap;@media (max-width:1000px){flex-direction:column;align-items:center;}"]),W=d.c.input.withConfig({displayName:"opt-form__Input",componentId:"sc-12qpfwk-1"})(["max-width:450px;width:100%;border:0;padding:10px;height:70px;box-sizing:border-box;"]),$=d.c.div.withConfig({displayName:"opt-form__Break",componentId:"sc-12qpfwk-2"})(["flex-basis:100%;height:0;"]),K=d.c.div.withConfig({displayName:"opt-form__Button",componentId:"sc-12qpfwk-3"})(['height:80px;width:180px;margin:50px auto;cursor:pointer;background:url("../images/misc/google-play-badge.png");background-repeat:no-repeat;background-size:cover;background-position:center;']),U=d.c.p.withConfig({displayName:"opt-form__Text",componentId:"sc-12qpfwk-4"})(["font-size:19.2px;color:white;text-align:center;@media (max-width:600px){font-size:16px;line-height:22px;}"]);function X(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(J,t,n)}X.Input=i.a.forwardRef((function(e,n){return i.a.createElement(W,Object.assign({ref:n},e))})),X.Button=function(e){var n=Object.assign({},e),t="parceiro"===(document.location.host.split(".")[1]?document.location.host.split(".")[1]:document.location.host)?"https://play.google.com/store/apps/details?id=com.nuppin.company":"https://play.google.com/store/apps/details?id=com.nuppin";return i.a.createElement(K,Object.assign({onClick:function(){return window.open(t,"_blank")}},n))},X.Text=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(U,t,n)},X.Break=function(e){var n=Object.assign({},e);return i.a.createElement($,n)};var Y="#fafafa",Z="#c7c7c7";function ee(){var e=Object(c.a)(["\n  font-size: 12px;\n  align-self: center;\n  /* background: green; */\n  color: ",";\n  margin: 10px;\n"]);return ee=function(){return e},e}function ne(){var e=Object(c.a)(["\n  font-size: 16px;\n  color: ",";\n  margin: 10px;\n  font-weight: bold;\n"]);return ne=function(){return e},e}function te(){var e=Object(c.a)(["\n  color: ",";\n  margin: 10px;\n  font-size: 14px;\n  text-decoration: none;\n  /* background: orange; */\n  &:hover {\n    color: ",";\n    transition: 200ms ease-in;\n  }\n"]);return te=function(){return e},e}function ae(){var e=Object(c.a)(["\n  color: ",";\n  margin: 10px;\n  font-size: 14px;\n  text-decoration: none;\n  /* background: orange; */\n  &:hover {\n    color: ",";\n    transition: 200ms ease-in;\n  }\n"]);return ae=function(){return e},e}function re(){var e=Object(c.a)(["\n  flex-wrap: wrap;\n  display: flex;\n  /* background: yellow; */\n  align-items: center;\n"]);return re=function(){return e},e}function ie(){var e=Object(c.a)(["\n  /* background: black; */\n  margin: 10px;\n"]);return ie=function(){return e},e}function oe(){var e=Object(c.a)(["\n  display: flex;\n  flex-direction: row-reverse;\n  flex-direction: column;\n  justify-content: center;\n  max-width: 1000px;\n  margin: 0 auto;\n  /* background: red; */\n"]);return oe=function(){return e},e}function ce(){var e=Object(c.a)(["\n  padding: 20px 0;\n  background: ",";\n"]);return ce=function(){return e},e}var de=d.c.div(ce(),(function(e){return e.theme.feature_background})),le=d.c.div(oe()),me=d.c.div(ie()),pe=d.c.div(re()),ue=Object(d.c)(u.b)(ae(),Z,(function(e){return e.theme.primary_color})),se=d.c.a(te(),Z,(function(e){return e.theme.primary_color})),ge=d.c.p(ne(),Z),he=d.c.p(ee(),Z);function fe(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(de,t,n)}fe.Wrapper=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(le,t,n)},fe.Row=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(pe,t,n)},fe.Column=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(me,t,n)},fe.Link=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return t.href?i.a.createElement(se,Object.assign({target:"_blank"},t),n):i.a.createElement(ue,t,n)},fe.Title=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(ge,t,n)},fe.Text=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(he,t,n)};var xe=t(12),be=t(29),we=d.c.div.withConfig({displayName:"header__Background",componentId:"iaxudy-0"})(["display:flex;flex-direction:column;background:",",url(",");background-repeat:no-repeat;background-size:cover;background-position:center;"],(function(e){return e.theme.radial_background}),(function(e){return e.src})),ve=d.c.div.withConfig({displayName:"header__Container",componentId:"iaxudy-1"})(["display:flex;padding:18px 56px;justify-content:space-between;align-items:center;a{display:flex;}@media (max-width:1000px){padding:18px 30px;}"]),ye=d.c.p.withConfig({displayName:"header__Link",componentId:"iaxudy-2"})(["z-index:999;text-decoration:none;margin-right:30px;font-weight:",";cursor:pointer;&:hover{font-weight:bold;}&:last-of-type{margin-right:0;}"],(function(e){return"true"===e.active?"700":"normal"})),Ee=d.c.div.withConfig({displayName:"header__Group",componentId:"iaxudy-3"})(["display:flex;align-items:center;"]),_e=d.c.input.withConfig({displayName:"header__SearchInput",componentId:"iaxudy-4"})(["background-color:#44444459;border-radius:5px;color:white;outline:none;z-index:999;border:1px solid white;transition:width 0.5s;height:30px;font-size:14px;margin-left:",";padding:",";opacity:",";width:",";"],(function(e){return!0===e.active?"10px":"0"}),(function(e){return!0===e.active?"0 10px":"0"}),(function(e){return!0===e.active?"1":"0"}),(function(e){return!0===e.active?"200px":"0px"})),je=d.c.div.withConfig({displayName:"header__Search",componentId:"iaxudy-5"})(["display:flex;align-items:center;z-index:999;@media (max-width:700px){display:none;}"]),ke=Object(d.c)(xe.e).withConfig({displayName:"header__SearchIcon",componentId:"iaxudy-6"})(["border:0;color:white;padding:5px;border-radius:5px;margin:0 8px;z-index:10;cursor:pointer;&:hover{background-color:rgba(0,0,0,0.2);}align-items:center;"]),Oe=Object(d.c)(be.a).withConfig({displayName:"header__PersonIcon",componentId:"iaxudy-7"})(["border:0;color:gray;padding:5px;border-radius:50%;margin:0 8px;z-index:10;cursor:pointer;background:white;&:hover{background:rgba(0,0,0,0.2);color:white;}align-items:center;",""],(function(e){return e.nav?"background: rgba(0, 0, 0, 0.2);color: white":""})),Ce=d.c.a.withConfig({displayName:"header__ButtonLink",componentId:"iaxudy-8"})(["background-color:#222;color:white;font-size:15px;border-radius:3px;padding:10px 20px;cursor:pointer;text-decoration:none;box-shadow:0 3px 10px rgba(0,0,0,0.2);&:hover{background:#303030;}"]),ze=d.c.button.withConfig({displayName:"header__Picture",componentId:"iaxudy-9"})(["background:url(",");background-size:contain;border:0;width:32px;height:32px;border-radius:50%;&:hover{opacity:0.7;}cursor:pointer;"],(function(e){return e.src})),Ie=Object(d.c)(_.b).withConfig({displayName:"header__CartIcon",componentId:"iaxudy-10"})(["border:0;color:white;padding:5px;border-radius:5px;margin:0 8px;z-index:10;cursor:pointer;&:hover{background-color:rgba(0,0,0,0.2);}align-items:center;",""],(function(e){return e.nav?"background-color: rgba(0, 0, 0, 0.2)":""})),Ne=Object(d.c)(xe.d).withConfig({displayName:"header__OrderIcon",componentId:"iaxudy-11"})(["border:0;color:white;padding:5px;border-radius:5px;margin:0 8px;z-index:10;cursor:pointer;&:hover{background-color:rgba(0,0,0,0.2);}align-items:center;",""],(function(e){return e.nav?"background-color: rgba(0, 0, 0, 0.2)":""})),Te=d.c.div.withConfig({displayName:"header__Dropdown",componentId:"iaxudy-12"})(["display:none;position:absolute;background-color:white;box-shadow:0px 1px 9px 0px rgba(214,210,214,1);border-radius:5px;padding:20px;width:100px;top:32px;right:0px;z-index:2;",":last-of-type ","{cursor:pointer;}","{margin-bottom:10px;&:last-of-type{margin-bottom:0;}",",","{cursor:default;}}button{margin-right:10px;}p{font-size:12px;margin-bottom:0;margin-top:0;}"],Ee,ye,Ee,ye,ze),qe=d.c.div.withConfig({displayName:"header__Profile",componentId:"iaxudy-13"})(["display:flex;align-items:center;margin-left:20px;position:relative;button{cursor:pointer;}"]),Se=d.c.div.withConfig({displayName:"header__FeatureBlur",componentId:"iaxudy-14"})(["display:flex;margin:-100px 0 0 0;padding:175px 0 250px 0;flex-direction:column;width:100%;background:linear-gradient(to right,#000 30%,transparent 100%);justify-content:space-between;@media (max-width:1100px){padding:130px 0 100px 0;background:linear-gradient(to right,#000 40%,transparent 100%);}@media (max-width:1000px){margin:-100px 0 0 0;padding:115px 0 50px 0;background:linear-gradient(to right,#000 30%,transparent 100%);}"]),Pe=d.c.div.withConfig({displayName:"header__Feature",componentId:"iaxudy-15"})(["display:flex;flex-direction:column;width:25%;padding:0 56px 0 56px;justify-content:space-between;a{display:flex;}@media (max-width:1100px){width:40%;padding:0 56px 0 56px;}@media (max-width:1000px){width:50%;padding:0 30px 0 30px;}"]),Fe=d.c.h2.withConfig({displayName:"header__FeatureCallOut",componentId:"iaxudy-16"})(["color:white;font-size:50px;line-height:normal;font-weight:bold;text-shadow:2px 2px 4px rgba(0,0,0,0.45);margin:0;@media (max-width:1100px){font-size:25px;}"]),Be=d.c.p.withConfig({displayName:"header__Text",componentId:"iaxudy-17"})(["color:white;font-size:22px;line-height:normal;text-shadow:2px 2px 4px rgba(0,0,0,0.45);overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:5;-webkit-box-orient:vertical;@media (max-width:1100px){font-size:16px;}"]),Le=d.c.img.withConfig({displayName:"header__Logo",componentId:"iaxudy-18"})(["max-height:50px;max-width:50px;width:50px;height:50px;z-index:999;border-radius:50%;@media (min-width:1000px){max-height:90px;max-width:90px;height:90px;width:90px;}"]),De=d.c.button.withConfig({displayName:"header__PlayButton",componentId:"iaxudy-19"})(["box-shadow:0 0.6vw 1vw -0.4vw rgba(0,0,0,0.35);background-color:#e6e6e6;color:#000;border-width:0;padding:10px 20px;border-radius:5px;max-width:130px;font-weight:bold;font-size:20px;margin-top:10px;cursor:pointer;transition:background-color 0.5s ease;&:hover{background-color:#ff1e1e;color:white;}"]);function Ae(e){var n=e.bg,t=void 0===n||n,a=e.children,r=Object(h.a)(e,["bg","children"]);return t?i.a.createElement(we,Object.assign({"data-testid":""},r),a):a}function Re(){var e=Object(c.a)(["\n  width: 50px;\n  height: 50px;\n  position: absolute;\n  top: 50%;\n  left: 50%;\n  margin-top: -100px;\n  margin-left: -22px;\n"]);return Re=function(){return e},e}function Ge(){var e=Object(c.a)(["\n  width: 100vw;\n  height: 100vh;\n  background-color: ",';\n  z-index: 999;\n  padding-top: 200px;\n\n  :after {\n    content: "";\n    display: flex;\n    margin: 0 auto;\n    background-image: ',";\n    background-size: contain;\n    background-repeat: no-repeat;\n    justify-content: space-around;\n    width: 150px;\n    height: 150px;\n    animation-name: spin;\n    animation-duration: 1000ms;\n    animation-iteration-count: infinite;\n    animation-timing-function: linear;\n  }\n\n  @-ms-keyframes spin {\n    from {\n      -ms-transform: rotate(0deg);\n    }\n    to {\n      -ms-transform: rotate(360deg);\n    }\n  }\n\n  @-moz-keyframes spin {\n    from {\n      -moz-transform: rotate(0deg);\n    }\n    to {\n      -moz-transform: rotate(360deg);\n    }\n  }\n\n  @-webkit-keyframes spin {\n    from {\n      -webkit-transform: rotate(0deg);\n    }\n    to {\n      -webkit-transform: rotate(360deg);\n    }\n  }\n\n  @keyframes spin {\n    from {\n      transform: rotate(0deg);\n    }\n    to {\n      transform: rotate(360deg);\n    }\n  }\n"]);return Ge=function(){return e},e}function He(){var e=Object(c.a)(["\n  body {\n    overflow: visible;\n  }\n"]);return He=function(){return e},e}function Me(){var e=Object(c.a)(["\n  body {\n    overflow: hidden;\n  }\n"]);return Me=function(){return e},e}Ae.Frame=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(ve,t,n)},Ae.Group=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Ee,t,n)},Ae.Logo=function(e){var n=e.to,t=Object(h.a)(e,["to"]);return i.a.createElement(u.b,{to:n},i.a.createElement(Le,t))},Ae.Search=function(e){var n=e.searchTerm,t=e.setSearchTerm,a=Object(h.a)(e,["searchTerm","setSearchTerm"]),o=Object(r.useState)(!1),c=Object(g.a)(o,2),d=c[0],l=c[1];return i.a.createElement(je,a,i.a.createElement(ke,{size:"24",onClick:function(){return l((function(e){return!e}))}}),i.a.createElement(_e,{value:n,onChange:function(e){var n=e.target;return t(n.value)},placeholder:"Procurar por...",active:d}))},Ae.Profile=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(qe,t,n)},Ae.FeatureBlur=function(e){var n=e.children;Object(h.a)(e,["children"]);return i.a.createElement(Se,null,n)},Ae.Feature=function(e){var n=e.children;Object(h.a)(e,["children"]);return i.a.createElement(Pe,null,n)},Ae.Picture=function(e){var n=Object.assign({},e);return n.src?i.a.createElement(ze,n):i.a.createElement(Oe,Object.assign({size:"24"},n))},Ae.OrderIcon=function(e){var n=Object.assign({},e);return i.a.createElement(Ne,n)},Ae.CartIcon=function(e){var n=Object.assign({},e);return i.a.createElement(Ie,n)},Ae.PersonIcon=function(e){var n=Object.assign({},e);return i.a.createElement(Oe,n)},Ae.Dropdown=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Te,t,n)},Ae.TextLink=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(ye,t,n)},Ae.PlayButton=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(De,t,n)},Ae.FeatureCallOut=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Fe,t,n)},Ae.Text=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Be,t,n)},Ae.ButtonLink=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Ce,t,n)};Object(d.b)(Me()),Object(d.b)(He()),d.c.div(Ge(),Y,(function(e){var n=e.url;return"url(".concat(n,")")})),d.c.img(Re());var Ve=d.c.div.withConfig({displayName:"notFound__Container",componentId:"uq2en5-0"})(["background-color:",";margin:2.5vh 5vh;"],Y),Qe=d.c.div.withConfig({displayName:"notFound__Card",componentId:"uq2en5-1"})(["box-shadow:0 3px 10px rgba(0,0,0,0.2);max-width:1000px;border-radius:10px;height:90vh;margin:0 auto;padding:10px;background:radial-gradient(circle,rgba(92,39,251,1) 0%,rgba(112,71,247,1) 100%);display:flex;justify-content:center;"]);function Je(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Ve,t,n)}Je.Card=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Qe,t,n)};var We=d.c.div.withConfig({displayName:"feature__Container",componentId:"aai8zm-0"})(["display:flex;flex-direction:column;border-bottom:8px solid ",";text-align:center;padding:165px 45px;@media (max-width:600px){padding:80px 20px;}"],(function(e){return e.theme.secondary_color})),$e=d.c.h1.withConfig({displayName:"feature__Title",componentId:"aai8zm-1"})(["color:white;max-width:640px;font-size:50px;margin:auto;@media (max-width:600px){font-size:30px;}"]),Ke=d.c.h2.withConfig({displayName:"feature__SubTitle",componentId:"aai8zm-2"})(["color:white;font-size:26px;max-width:640px;font-weight:normal;margin:16px auto;@media (max-width:600px){font-size:18px;}"]);function Ue(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(We,t,n)}function Xe(){return Object(r.useEffect)((function(){window.scrollTo(0,0)}),[]),i.a.createElement(Je,null,i.a.createElement(Je.Card,null,i.a.createElement(j.b,{size:"180",color:"white",style:{marginTop:"50px"}})))}function Ye(){return i.a.createElement(Xe,null)}Ue.Title=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement($e,t,n)},Ue.SubTitle=function(e){var n=e.children,t=Object(h.a)(e,["children"]);return i.a.createElement(Ke,t,n)};var Ze=document.location.host.split(".")[1]?document.location.host.split(".")[1]:document.location.host;function en(e){var n=e.children,t=e.logo;return i.a.createElement(Ae,{src:"../images/misc/misc_bk1.jpg"},i.a.createElement(Ae.Frame,null,i.a.createElement(Ae.Group,null,i.a.createElement(Ae.Logo,{to:"/",src:t,alt:""})),"localhost:3000"===Ze&&i.a.createElement(Ae.Group,null,i.a.createElement(Ae.ButtonLink,{href:"https://parceiro.nuppin.com"},"Seja um parceiro"))),n)}function nn(e){var n=e.jumbo;return i.a.createElement(i.a.Fragment,null,n.map((function(e){return i.a.createElement(Q.Container,{background:e.background},e.data.map((function(e){return i.a.createElement(Q,{key:e.id,direction:e.direction},i.a.createElement(Q.Pane,{style:{padding:"row-reverse"===e.direction?"0 0 0 5%":"0 50px"}},i.a.createElement(Q.Title,null,e.title),i.a.createElement(Q.SubTitle,null,e.subTitle)),i.a.createElement(Q.Pane,null,i.a.createElement(Q.Image,{src:e.image,alt:e.alt})))})))})))}function tn(e){var n=e.faq;return i.a.createElement(O,null,i.a.createElement(O.Title,null,"Perguntas Frequentes"),i.a.createElement(O.Frame,null,n.map((function(e){return i.a.createElement(O.Item,{key:e.id},i.a.createElement(O.Header,null,e.header),i.a.createElement(O.Body,null,e.body))}))),i.a.createElement(X.Button,null))}function an(){return i.a.createElement(fe,null,i.a.createElement(fe.Wrapper,null,i.a.createElement(fe.Row,{style:{justifyContent:"space-between"}},i.a.createElement(fe.Column,null,i.a.createElement(fe.Title,null,"Sobre"),i.a.createElement(fe.Row,null,i.a.createElement(fe.Link,{to:"/terms"},"Termos de uso"))),i.a.createElement(fe.Column,null,i.a.createElement(fe.Title,null,"Redes Sociais"),i.a.createElement(fe.Row,null,i.a.createElement(fe.Link,{href:"https://facebook.com/nuppinbr"},i.a.createElement(xe.a,{size:"24"})),i.a.createElement(fe.Link,{href:"https://instagram.com/nuppinbr"},i.a.createElement(xe.b,{size:"24"})),i.a.createElement(fe.Link,{href:"https://linkedin.com/company/nuppin"},i.a.createElement(xe.c,{size:"24"}))))),i.a.createElement(fe.Row,null,i.a.createElement(fe.Column,null,i.a.createElement(fe.Text,null,"Nuppin. Todos os direitos reservados - 2020")))))}var rn=[{background:"#000",data:[{id:1,title:"Conte com a gente",subTitle:"Temos uma plataforma onde unimos varios empreendedores com seus neg\xf3cios de uma maneira local para atrair um novo publico e dar mais visibilidade para cada neg\xf3cio e consequentemente aumentar as vendas",image:"/images/misc/growth_2.svg",alt:"",direction:"row"}]},{background:"#000",data:[{id:2,title:"Tenha seu pr\xf3prio site",subTitle:"Al\xe9m de poder fazer parte desse grupo, voc\xea pode ter o seu pr\xf3prio site em poucos cliques, e divulgar seu neg\xf3cio de uma forma mais direcionada, al\xe9m disso, pode ainda divulgar esse seu site para os cliente que encontram sua loja dentro do nuppin, ou focar apenas no site",image:"/images/misc/web_dev.svg",alt:"",direction:"row-reverse"}]},{background:"#000",data:[{id:3,title:"V\xe1 al\xe9m do site",subTitle:"Hoje em dia tudo \xe9 muito mov\xe9l, e apesar do site ter um visual lindo tanto no desktop quanto no smartphone, tem clientes que gostam de ter o aplicativo da sua loja favorita a disposi\xe7\xe3o. Por isso, temos a op\xe7\xe3o de al\xe9m de ter seu pr\xf3prio site, ter tamb\xe9m seu pr\xf3prio aplicativo. Para oferecer multiplos canais para seus clientes",image:"/images/misc/location_color.svg",alt:"",direction:"row"}]}],on=[{background:"#FF585D",data:[{id:1,title:"Conhe\xe7a os neg\xf3cios da sua regi\xe7\xe3o",subTitle:"Tem muitos lugares legais que ainda n\xe3o tem muita divulga\xe7\xe3o no internet, estamos fazendo nosso melhor para ajudar o micro e pequeno empreendedor a conseguir isso.",image:"/images/misc/welcome.svg",alt:"",direction:"row-reverse"}]},{background:"#FF585D",data:[{id:2,title:"Tudo o que amamos",subTitle:"Os nossos parceiros s\xe3o incriveis, tem tudo o que a gente precisa no dia a dia. Roupa, maquiagem, acessorios, lanche, comida fit, barbearia, manicure.. E muito mais, tudo com um excelente atendimento",image:"/images/misc/healthy_eating.svg",alt:"",direction:"row"}]},{background:"#FF585D",data:[{id:3,title:"Simplicidade",subTitle:"Em poucos toques voc\xea j\xe1 verifica se tem o que voc\xea deseja proximo a voc\xea e ao encontrar o que precisa, \xe9 muito simples de fazer o seu pedido. Baixe o app e experimente!",image:"/images/misc/meditation.svg",alt:"",direction:"row-reverse"}]}],cn=[{id:1,header:"O que \xe9 o nuppin?",body:"Nuppin \xe9 uma plataforma onde micro e pequeno empreendedores tem ferramentas para publicar e gerenciar uma loja online. Venda mais, encontre mais clientes, aprenda novas tecnicas de como gerenciar seu neg\xf3cio com a gente!"},{id:2,header:"Quanto o nuppin custa?",body:"Tenha acesso ao nuppin no seu smartphone, tablet ou computador. Os planos do nuppin iniciam no valor de R$19,90. Temos 3 planos para necessidades diferentes"},{id:3,header:"Eu pago taxa para usar?",body:"Existem diversar formar de trabalhar com o nuppin, com ou sem taxa. Se voc\xea usar o plano da lojas em grupo, tem uma taxa de 9% sobre as vendas, isso por causa do custo do marketing que temos nesse canal para te trazer mais clientes. Mas para quem n\xe3o quer usar esse canal, temos os planos com site ou aplicativo pr\xf3prio, onde n\xe3o existe taxa sobre as vendas."},{id:4,header:"Que tipo de neg\xf3cio posso cadastrar?",body:"Trabalhamos com 3 categorias. Produtos, Alimentos e Servi\xe7os de Beleza. Se o seu neg\xf3cio est\xe1 dentro dessas categorias voc\xea pode cadastra-lo com a gente"},{id:5,header:"Qual a forma de pagamento?",body:"A forma de pagamento \xe9 mensal, atraves de pagamento via boleto. Voc\xea pode cancelar o seu plano a qualquer momento, ap\xf3s voc\xea cancelar o plano, n\xe3o ser\xe1 gerado mais boletos"}],dn=[{id:1,header:"Como comprar no nuppin?",body:"Para comprar no nuppin, voc\xea precisa fazer o download do aplicativo na PlayStore. Ap\xf3s ser instalado, voc\xea vai criar a sua conta, colocar seu endere\xe7o e assim vai aparecer os estabelecimentos perto de voc\xea para escolher o que precisa."},{id:2,header:"Qual a forma de pagamento?",body:"Oferecemos diversas op\xe7\xf5es de pagamento, cada estabelecimento \xe9 responsavel por definir quais estar\xe3o disponiveis para os clientes"},{id:3,header:"N\xe3o tem estabelecimento por perto, o que fazer?",body:"Estamos trabalhando para aumentar nossa \xe1rea de atua\xe7\xe3o, mas realmente ainda n\xe3o conseguimos ter estabelecimentos parceiros em todos os lugar, mas existem algumas coisas que podem ser feitas para ajudar nisso. Voc\xea tem a op\xe7\xe3o de compartilhar com pessoas que se interessariam em ingressar como nosso parceiro, ou voc\xea mesmo ser o primeiro da sua regi\xe3o"},,{id:4,header:"Como fa\xe7o para me tornar um parceiro?",body:"Para isso, voc\xea precisa fazer o download do aplicativo do nuppin para empresas, l\xe1 voc\xea pode usar o mesmo cadastro que usou no nuppin. Vai inserir os dados do seu neg\xf3cio, escolher o plano, organizar os produtos/servi\xe7os e j\xe1 pode ficar online. Bem simples.\nMais informa\xe7\xf5es em: https://parceiro.nuppin.com"}];function ln(){return i.a.createElement(i.a.Fragment,null,i.a.createElement(en,{logo:"../images/misc/company_nuppin.png"},i.a.createElement(Ue,null,i.a.createElement(Ue.Title,null,"Pensado para pequeno e micro empreendedores"),i.a.createElement(Ue.SubTitle,null,"Venda com a gente, no seu dominio pr\xf3prio ou aplicativo. Muito simples!"),i.a.createElement(X.Button,null))),i.a.createElement(nn,{jumbo:rn}),i.a.createElement(tn,{faq:cn}),i.a.createElement(an,null))}function mn(){return i.a.createElement(i.a.Fragment,null,i.a.createElement(en,{logo:"../images/misc/nuppin.png"},i.a.createElement(Ue,null,i.a.createElement(Ue.Title,null,"Produtos, Alimentos e Servi\xe7os de Beleza"),i.a.createElement(Ue.SubTitle,null,"Encontre de um jeito simples e na plataforma mais parceira do micro e pequeno empreendedor"),i.a.createElement(X.Button,null))),i.a.createElement(nn,{jumbo:on}),i.a.createElement(tn,{faq:dn}),i.a.createElement(an,null))}function pn(e){var n=e.user,t=e.children,a=Object(h.a)(e,["user","children"]);return i.a.createElement(s.b,Object.assign({},a,{render:function(e){var a=e.location;return n?t:n?null:i.a.createElement(s.a,{to:{pathname:"signin",state:{from:a}}})}}))}var un,sn=document.location.host.split(".")[1]?document.location.host.split(".")[1]:document.location.host;function gn(){return"parceiro"===sn?i.a.createElement(u.a,null,i.a.createElement(s.d,null,i.a.createElement(pn,{exact:!0,user:!0,path:"/"},i.a.createElement(ln,null)),i.a.createElement(pn,{user:!0,path:"/"},i.a.createElement(Ye,null)))):"localhost:3000"===sn?i.a.createElement(u.a,null,i.a.createElement(s.d,null,i.a.createElement(pn,{exact:!0,user:!0,path:"/"},i.a.createElement(mn,null)),i.a.createElement(pn,{user:!0,path:"/"},i.a.createElement(Ye,null)))):i.a.createElement(u.a,null,i.a.createElement(pn,{user:!0,path:"/"},i.a.createElement(Ye,null)))}var hn=document.location.host.split(".")[1]?document.location.host.split(".")[1]:document.location.host,fn=(un={primary_color:"#FF585D",feature_background:"black",secondary_color:"#222"},Object(a.a)(un,"feature_background","#000"),Object(a.a)(un,"faq_border","#222"),Object(a.a)(un,"feature_color","white"),Object(a.a)(un,"faq_item","#303030"),Object(a.a)(un,"radial_background","radial-gradient(circle,rgba(2, 0, 36, 0.9) 0%,rgba(17, 51, 111, 0.9) 0%,rgba(10, 122, 174, 0.9) 45%,rgba(14, 82, 138, 0.9) 58%,rgba(20, 20, 83, 0.9) 83%,rgba(0, 212, 255, 0.9) 100%)"),un),xn="parceiro"===hn?fn:{primary_color:"#FF585D",primaryDark:"#c61c33",primaryLight:"#ff8c8a",feature_background:"#fafafa",feature_color:"#222",faq_border:"#e5e5e5",secondary_color:"#ff8c8a",faq_item:"#c7c7c7",radial_background:"radial-gradient(circle at 22% 15%, rgba(45, 45, 45,0.05) 0%, rgba(45, 45, 45,0.05) 50%,rgba(95, 95, 95,0.05) 50%, rgba(95, 95, 95,0.05) 100%),radial-gradient(circle at 83% 16%, rgba(122, 122, 122,0.05) 0%, rgba(122, 122, 122,0.05) 50%,rgba(194, 194, 194,0.05) 50%, rgba(194, 194, 194,0.05) 100%),radial-gradient(circle at 74% 7%, rgba(82, 82, 82,0.05) 0%, rgba(82, 82, 82,0.05) 50%,rgba(230, 230, 230,0.05) 50%, rgba(230, 230, 230,0.05) 100%),linear-gradient(90deg, rgb(243, 136, 126, 0.97),rgb(251, 43, 71, 0.99))"},bn="parceiro"===hn?{title:"Parceiro Nuppin",metaDescription:" Plataforma Nuppin Empresas",favicon:"../images/misc/company_nuppin.png"}:{title:"Nuppin",metaDescription:"Produto, Alimentos e Servi\xe7os de Beleza",favicon:"../images/misc/nuppin.png"};document.title=bn.title,document.querySelector('meta[name="description"]').setAttribute("content",bn.metaDescription),document.querySelector('link[rel="icon"]').setAttribute("href",bn.favicon),Object(o.render)(i.a.createElement(i.a.StrictMode,null,i.a.createElement(p,null),i.a.createElement(d.a,{theme:xn},i.a.createElement(gn,null))),document.getElementById("root"))}},[[30,1,2]]]);
//# sourceMappingURL=main.92e62f27.chunk.js.map