<!--
SPT = "--请选择省份--";
SCT = "--请选择城市--";
SAT = "--请选择地区--";
ShowT = 1; //提示文字 0:不显示 1:显示
// PCAD = "北京市$市辖区,东城区,西城区,朝阳区,丰台区,石景山区,海淀区,门头沟区,房山区,通州区,顺义区,昌平区,大兴区,怀柔区,平谷区|市辖县,密云县,延庆县#天津市$市辖区,和平区,河东区,河西区,南开区,河北区,红桥区,东丽区,西青区,津南区,北辰区,武清区,宝坻区,滨海新区|市辖县,宁河县,静海县,蓟县#河北省$石家庄市,市辖区,长安区,桥东区,桥西区,新华区,井陉矿区,裕华区,井陉县,正定县,栾城县,行唐县,灵寿县,高邑县,深泽县,赞皇县,无极县,平山县,元氏县,赵县,辛集市,藁城市,晋州市,新乐市,鹿泉市|唐山市,市辖区,路南区,路北区,古冶区,开平区,丰南区,丰润区,滦县,滦南县,乐亭县,迁西县,玉田县,唐海县,遵化市,迁安市|秦皇岛市,市辖区,海港区,山海关区,北戴河区,青龙满族自治县,昌黎县,抚宁县,卢龙县|邯郸市,市辖区,邯山区,丛台区,复兴区,峰峰矿区,邯郸县,临漳县,成安县,大名县,涉县,磁县,肥乡县,永年县,邱县,鸡泽县,广平县,馆陶县,魏县,曲周县,武安市|邢台市,市辖区,桥东区,桥西区,邢台县,临城县,内丘县,柏乡县,隆尧县,任县,南和县,宁晋县,巨鹿县,新河县,广宗县,平乡县,威县,清河县,临西县,南宫市,沙河市|保定市,市辖区,新市区,北市区,南市区,满城县,清苑县,涞水县,阜平县,徐水县,定兴县,唐县,高阳县,容城县,涞源县,望都县,安新县,易县,曲阳县,蠡县,顺平县,博野县,雄县,涿州市,定州市,安国市,高碑店市|张家口市,市辖区,桥东区,桥西区,宣化区,下花园区,宣化县,张北县,康保县,沽源县,尚义县,蔚县,阳原县,怀安县,万全县,怀来县,涿鹿县,赤城县,崇礼县|承德市,市辖区,双桥区,双滦区,鹰手营子矿区,承德县,兴隆县,平泉县,滦平县,隆化县,丰宁满族自治县,宽城满族自治县,围场满族蒙古族自治县|沧州市,市辖区,新华区,运河区,沧县,青县,东光县,海兴县,盐山县,肃宁县,南皮县,吴桥县,献县,孟村回族自治县,泊头市,任丘市,黄骅市,河间市|廊坊市,市辖区,安次区,广阳区,固安县,永清县,香河县,大城县,文安县,大厂回族自治县,霸州市,三河市|衡水市,市辖区,桃城区,枣强县,武邑县,武强县,饶阳县,安平县,故城县,景县,阜城县,冀州市,深州市#山西省$太原市,市辖区,小店区,迎泽区,杏花岭区,尖草坪区,万柏林区,晋源区,清徐县,阳曲县,娄烦县,古交市|大同市,市辖区,城区,矿区,南郊区,新荣区,阳高县,天镇县,广灵县,灵丘县,浑源县,左云县,大同县|阳泉市,市辖区,城区,矿区,郊区,平定县,盂县|长治市,市辖区,城区,郊区,长治县,襄垣县,屯留县,平顺县,黎城县,壶关县,长子县,武乡县,沁县,沁源县,潞城市|晋城市,市辖区,城区,沁水县,阳城县,陵川县,泽州县,高平市|朔州市,市辖区,朔城区,平鲁区,山阴县,应县,右玉县,怀仁县|晋中市,市辖区,榆次区,榆社县,左权县,和顺县,昔阳县,寿阳县,太谷县,祁县,平遥县,灵石县,介休市|运城市,市辖区,盐湖区,临猗县,万荣县,闻喜县,稷山县,新绛县,绛县,垣曲县,夏县,平陆县,芮城县,永济市,河津市|忻州市,市辖区,忻府区,定襄县,五台县,代县,繁峙县,宁武县,静乐县,神池县,五寨县,岢岚县,河曲县,保德县,偏关县,原平市|临汾市,市辖区,尧都区,曲沃县,翼城县,襄汾县,洪洞县,古县,安泽县,浮山县,吉县,乡宁县,大宁县,隰县,永和县,蒲县,汾西县,侯马市,霍州市|吕梁市,市辖区,离石区,文水县,交城县,兴县,临县,柳林县,石楼县,岚县,方山县,中阳县,交口县,孝义市,汾阳市#内蒙古自治区$呼和浩特市,市辖区,新城区,回民区,玉泉区,赛罕区,土默特左旗,托克托县,和林格尔县,清水河县,武川县|包头市,市辖区,东河区,昆都仑区,青山区,石拐区,白云鄂博矿区,九原区,土默特右旗,固阳县,达尔罕茂明安联合旗|乌海市,市辖区,海勃湾区,海南区,乌达区|赤峰市,市辖区,红山区,元宝山区,松山区,阿鲁科尔沁旗,巴林左旗,巴林右旗,林西县,克什克腾旗,翁牛特旗,喀喇沁旗,宁城县,敖汉旗|通辽市,市辖区,科尔沁区,科尔沁左翼中旗,科尔沁左翼后旗,开鲁县,库伦旗,奈曼旗,扎鲁特旗,霍林郭勒市|鄂尔多斯市,市辖区,东胜区,达拉特旗,准格尔旗,鄂托克前旗,鄂托克旗,杭锦旗,乌审旗,伊金霍洛旗|呼伦贝尔市,市辖区,海拉尔区,阿荣旗,莫力达瓦达斡尔族自治旗,鄂伦春自治旗,鄂温克族自治旗,陈巴尔虎旗,新巴尔虎左旗,新巴尔虎右旗,满洲里市,牙克石市,扎兰屯市,额尔古纳市,根河市|巴彦淖尔市,市辖区,临河区,五原县,磴口县,乌拉特前旗,乌拉特中旗,乌拉特后旗,杭锦后旗|乌兰察布市,市辖区,集宁区,卓资县,化德县,商都县,兴和县,凉城县,察哈尔右翼前旗,察哈尔右翼中旗,察哈尔右翼后旗,四子王旗,丰镇市|兴安盟,乌兰浩特市,阿尔山市,科尔沁右翼前旗,科尔沁右翼中旗,扎赉特旗,突泉县|锡林郭勒盟,二连浩特市,锡林浩特市,阿巴嘎旗,苏尼特左旗,苏尼特右旗,东乌珠穆沁旗,西乌珠穆沁旗,太仆寺旗,镶黄旗,正镶白旗,正蓝旗,多伦县|阿拉善盟,阿拉善左旗,阿拉善右旗,额济纳旗#辽宁省$沈阳市,市辖区,和平区,沈河区,大东区,皇姑区,铁西区,苏家屯区,东陵区,沈北新区,于洪区,辽中县,康平县,法库县,新民市|大连市,市辖区,中山区,西岗区,沙河口区,甘井子区,旅顺口区,金州区,长海县,瓦房店市,普兰店市,庄河市|鞍山市,市辖区,铁东区,铁西区,立山区,千山区,台安县,岫岩满族自治县,海城市|抚顺市,市辖区,新抚区,东洲区,望花区,顺城区,抚顺县,新宾满族自治县,清原满族自治县|本溪市,市辖区,平山区,溪湖区,明山区,南芬区,本溪满族自治县,桓仁满族自治县|丹东市,市辖区,元宝区,振兴区,振安区,宽甸满族自治县,东港市,凤城市|锦州市,市辖区,古塔区,凌河区,太和区,黑山县,义县,凌海市,北镇市|营口市,市辖区,站前区,西市区,鲅鱼圈区,老边区,盖州市,大石桥市|阜新市,市辖区,海州区,新邱区,太平区,清河门区,细河区,阜新蒙古族自治县,彰武县|辽阳市,市辖区,白塔区,文圣区,宏伟区,弓长岭区,太子河区,辽阳县,灯塔市|盘锦市,市辖区,双台子区,兴隆台区,大洼县,盘山县|铁岭市,市辖区,银州区,清河区,铁岭县,西丰县,昌图县,调兵山市,开原市|朝阳市,市辖区,双塔区,龙城区,朝阳县,建平县,喀喇沁左翼蒙古族自治县,北票市,凌源市|葫芦岛市,市辖区,连山区,龙港区,南票区,绥中县,建昌县,兴城市#吉林省$长春市,市辖区,南关区,宽城区,朝阳区,二道区,绿园区,双阳区,农安县,九台市,榆树市,德惠市|吉林市,市辖区,昌邑区,龙潭区,船营区,丰满区,永吉县,蛟河市,桦甸市,舒兰市,磐石市|四平市,市辖区,铁西区,铁东区,梨树县,伊通满族自治县,公主岭市,双辽市|辽源市,市辖区,龙山区,西安区,东丰县,东辽县|通化市,市辖区,东昌区,二道江区,通化县,辉南县,柳河县,梅河口市,集安市|白山市,市辖区,八道江区,江源区,抚松县,靖宇县,长白朝鲜族自治县,临江市|松原市,市辖区,宁江区,前郭尔罗斯蒙古族自治县,长岭县,乾安县,扶余县|白城市,市辖区,洮北区,镇赉县,通榆县,洮南市,大安市|延边朝鲜族自治州,延吉市,图们市,敦化市,珲春市,龙井市,和龙市,汪清县,安图县#黑龙江省$哈尔滨市,市辖区,道里区,南岗区,道外区,平房区,松北区,香坊区,呼兰区,阿城区,依兰县,方正县,宾县,巴彦县,木兰县,通河县,延寿县,双城市,尚志市,五常市|齐齐哈尔市,市辖区,龙沙区,建华区,铁锋区,昂昂溪区,富拉尔基区,碾子山区,梅里斯达斡尔族区,龙江县,依安县,泰来县,甘南县,富裕县,克山县,克东县,拜泉县,讷河市|鸡西市,市辖区,鸡冠区,恒山区,滴道区,梨树区,城子河区,麻山区,鸡东县,虎林市,密山市|鹤岗市,市辖区,向阳区,工农区,南山区,兴安区,东山区,兴山区,萝北县,绥滨县|双鸭山市,市辖区,尖山区,岭东区,四方台区,宝山区,集贤县,友谊县,宝清县,饶河县|大庆市,市辖区,萨尔图区,龙凤区,让胡路区,红岗区,大同区,肇州县,肇源县,林甸县,杜尔伯特蒙古族自治县|伊春市,市辖区,伊春区,南岔区,友好区,西林区,翠峦区,新青区,美溪区,金山屯区,五营区,乌马河区,汤旺河区,带岭区,乌伊岭区,红星区,上甘岭区,嘉荫县,铁力市|佳木斯市,市辖区,向阳区,前进区,东风区,郊区,桦南县,桦川县,汤原县,抚远县,同江市,富锦市|七台河市,市辖区,新兴区,桃山区,茄子河区,勃利县|牡丹江市,市辖区,东安区,阳明区,爱民区,西安区,东宁县,林口县,绥芬河市,海林市,宁安市,穆棱市|黑河市,市辖区,爱辉区,嫩江县,逊克县,孙吴县,北安市,五大连池市|绥化市,市辖区,北林区,望奎县,兰西县,青冈县,庆安县,明水县,绥棱县,安达市,肇东市,海伦市|大兴安岭地区,呼玛县,塔河县,漠河县#上海市$市辖区,黄浦区,徐汇区,长宁区,静安区,普陀区,闸北区,虹口区,杨浦区,闵行区,宝山区,嘉定区,浦东新区,金山区,松江区,青浦区,奉贤区|市辖县,崇明县#江苏省$南京市,市辖区,玄武区,白下区,秦淮区,建邺区,鼓楼区,下关区,浦口区,栖霞区,雨花台区,江宁区,六合区,溧水县,高淳县|无锡市,市辖区,崇安区,南长区,北塘区,锡山区,惠山区,滨湖区,江阴市,宜兴市|徐州市,市辖区,鼓楼区,云龙区,贾汪区,泉山区,铜山区,丰县,沛县,睢宁县,新沂市,邳州市|常州市,市辖区,天宁区,钟楼区,戚墅堰区,新北区,武进区,溧阳市,金坛市|苏州市,市辖区,沧浪区,平江区,金阊区,虎丘区,吴中区,相城区,常熟市,张家港市,昆山市,吴江市,太仓市|南通市,市辖区,崇川区,港闸区,通州区,海安县,如东县,启东市,如皋市,海门市|连云港市,市辖区,连云区,新浦区,海州区,赣榆县,东海县,灌云县,灌南县|淮安市,市辖区,清河区,楚州区,淮阴区,清浦区,涟水县,洪泽县,盱眙县,金湖县|盐城市,市辖区,亭湖区,盐都区,响水县,滨海县,阜宁县,射阳县,建湖县,东台市,大丰市|扬州市,市辖区,广陵区,邗江区,江都区,宝应县,仪征市,高邮市|镇江市,市辖区,京口区,润州区,丹徒区,丹阳市,扬中市,句容市|泰州市,市辖区,海陵区,高港区,兴化市,靖江市,泰兴市,姜堰市|宿迁市,市辖区,宿城区,宿豫区,沭阳县,泗阳县,泗洪县#浙江省$杭州市,市辖区,上城区,下城区,江干区,拱墅区,西湖区,滨江区,萧山区,余杭区,桐庐县,淳安县,建德市,富阳市,临安市|宁波市,市辖区,海曙区,江东区,江北区,北仑区,镇海区,鄞州区,象山县,宁海县,余姚市,慈溪市,奉化市|温州市,市辖区,鹿城区,龙湾区,瓯海区,洞头县,永嘉县,平阳县,苍南县,文成县,泰顺县,瑞安市,乐清市|嘉兴市,市辖区,南湖区,秀洲区,嘉善县,海盐县,海宁市,平湖市,桐乡市|湖州市,市辖区,吴兴区,南浔区,德清县,长兴县,安吉县|绍兴市,市辖区,越城区,绍兴县,新昌县,诸暨市,上虞市,嵊州市|金华市,市辖区,婺城区,金东区,武义县,浦江县,磐安县,兰溪市,义乌市,东阳市,永康市|衢州市,市辖区,柯城区,衢江区,常山县,开化县,龙游县,江山市|舟山市,市辖区,定海区,普陀区,岱山县,嵊泗县|台州市,市辖区,椒江区,黄岩区,路桥区,玉环县,三门县,天台县,仙居县,温岭市,临海市|丽水市,市辖区,莲都区,青田县,缙云县,遂昌县,松阳县,云和县,庆元县,景宁畲族自治县,龙泉市#安徽省$合肥市,市辖区,瑶海区,庐阳区,蜀山区,包河区,长丰县,肥东县,肥西县,庐江县,巢湖市|芜湖市,市辖区,镜湖区,弋江区,鸠江区,三山区,芜湖县,繁昌县,南陵县,无为县|蚌埠市,市辖区,龙子湖区,蚌山区,禹会区,淮上区,怀远县,五河县,固镇县|淮南市,市辖区,大通区,田家庵区,谢家集区,八公山区,潘集区,凤台县|马鞍山市,市辖区,金家庄区,花山区,雨山区,当涂县,含山县,和县|淮北市,市辖区,杜集区,相山区,烈山区,濉溪县|铜陵市,市辖区,铜官山区,狮子山区,郊区,铜陵县|安庆市,市辖区,迎江区,大观区,宜秀区,怀宁县,枞阳县,潜山县,太湖县,宿松县,望江县,岳西县,桐城市|黄山市,市辖区,屯溪区,黄山区,徽州区,歙县,休宁县,黟县,祁门县|滁州市,市辖区,琅琊区,南谯区,来安县,全椒县,定远县,凤阳县,天长市,明光市|阜阳市,市辖区,颍州区,颍东区,颍泉区,临泉县,太和县,阜南县,颍上县,界首市|宿州市,市辖区,埇桥区,砀山县,萧县,灵璧县,泗县|六安市,市辖区,金安区,裕安区,寿县,霍邱县,舒城县,金寨县,霍山县|亳州市,市辖区,谯城区,涡阳县,蒙城县,利辛县|池州市,市辖区,贵池区,东至县,石台县,青阳县|宣城市,市辖区,宣州区,郎溪县,广德县,泾县,绩溪县,旌德县,宁国市#福建省$福州市,市辖区,鼓楼区,台江区,仓山区,马尾区,晋安区,闽侯县,连江县,罗源县,闽清县,永泰县,平潭县,福清市,长乐市|厦门市,市辖区,思明区,海沧区,湖里区,集美区,同安区,翔安区|莆田市,市辖区,城厢区,涵江区,荔城区,秀屿区,仙游县|三明市,市辖区,梅列区,三元区,明溪县,清流县,宁化县,大田县,尤溪县,沙县,将乐县,泰宁县,建宁县,永安市|泉州市,市辖区,鲤城区,丰泽区,洛江区,泉港区,惠安县,安溪县,永春县,德化县,金门县,石狮市,晋江市,南安市|漳州市,市辖区,芗城区,龙文区,云霄县,漳浦县,诏安县,长泰县,东山县,南靖县,平和县,华安县,龙海市|南平市,市辖区,延平区,顺昌县,浦城县,光泽县,松溪县,政和县,邵武市,武夷山市,建瓯市,建阳市|龙岩市,市辖区,新罗区,长汀县,永定县,上杭县,武平县,连城县,漳平市|宁德市,市辖区,蕉城区,霞浦县,古田县,屏南县,寿宁县,周宁县,柘荣县,福安市,福鼎市#江西省$南昌市,市辖区,东湖区,西湖区,青云谱区,湾里区,青山湖区,南昌县,新建县,安义县,进贤县|景德镇市,市辖区,昌江区,珠山区,浮梁县,乐平市|萍乡市,市辖区,安源区,湘东区,莲花县,上栗县,芦溪县|九江市,市辖区,庐山区,浔阳区,九江县,武宁县,修水县,永修县,德安县,星子县,都昌县,湖口县,彭泽县,瑞昌市,共青城市|新余市,市辖区,渝水区,分宜县|鹰潭市,市辖区,月湖区,余江县,贵溪市|赣州市,市辖区,章贡区,赣县,信丰县,大余县,上犹县,崇义县,安远县,龙南县,定南县,全南县,宁都县,于都县,兴国县,会昌县,寻乌县,石城县,瑞金市,南康市|吉安市,市辖区,吉州区,青原区,吉安县,吉水县,峡江县,新干县,永丰县,泰和县,遂川县,万安县,安福县,永新县,井冈山市|宜春市,市辖区,袁州区,奉新县,万载县,上高县,宜丰县,靖安县,铜鼓县,丰城市,樟树市,高安市|抚州市,市辖区,临川区,南城县,黎川县,南丰县,崇仁县,乐安县,宜黄县,金溪县,资溪县,东乡县,广昌县|上饶市,市辖区,信州区,上饶县,广丰县,玉山县,铅山县,横峰县,弋阳县,余干县,鄱阳县,万年县,婺源县,德兴市#山东省$济南市,市辖区,历下区,市中区,槐荫区,天桥区,历城区,长清区,平阴县,济阳县,商河县,章丘市|青岛市,市辖区,市南区,市北区,四方区,黄岛区,崂山区,李沧区,城阳区,胶州市,即墨市,平度市,胶南市,莱西市|淄博市,市辖区,淄川区,张店区,博山区,临淄区,周村区,桓台县,高青县,沂源县|枣庄市,市辖区,市中区,薛城区,峄城区,台儿庄区,山亭区,滕州市|东营市,市辖区,东营区,河口区,垦利县,利津县,广饶县|烟台市,市辖区,芝罘区,福山区,牟平区,莱山区,长岛县,龙口市,莱阳市,莱州市,蓬莱市,招远市,栖霞市,海阳市|潍坊市,市辖区,潍城区,寒亭区,坊子区,奎文区,临朐县,昌乐县,青州市,诸城市,寿光市,安丘市,高密市,昌邑市|济宁市,市辖区,市中区,任城区,微山县,鱼台县,金乡县,嘉祥县,汶上县,泗水县,梁山县,曲阜市,兖州市,邹城市|泰安市,市辖区,泰山区,岱岳区,宁阳县,东平县,新泰市,肥城市|威海市,市辖区,环翠区,文登市,荣成市,乳山市|日照市,市辖区,东港区,岚山区,五莲县,莒县|莱芜市,市辖区,莱城区,钢城区|临沂市,市辖区,兰山区,罗庄区,河东区,沂南县,郯城县,沂水县,苍山县,费县,平邑县,莒南县,蒙阴县,临沭县|德州市,市辖区,德城区,陵县,宁津县,庆云县,临邑县,齐河县,平原县,夏津县,武城县,乐陵市,禹城市|聊城市,市辖区,东昌府区,阳谷县,莘县,茌平县,东阿县,冠县,高唐县,临清市|滨州市,市辖区,滨城区,惠民县,阳信县,无棣县,沾化县,博兴县,邹平县|菏泽市,市辖区,牡丹区,曹县,单县,成武县,巨野县,郓城县,鄄城县,定陶县,东明县#河南省$郑州市,市辖区,中原区,二七区,管城回族区,金水区,上街区,惠济区,中牟县,巩义市,荥阳市,新密市,新郑市,登封市|开封市,市辖区,龙亭区,顺河回族区,鼓楼区,禹王台区,金明区,杞县,通许县,尉氏县,开封县,兰考县|洛阳市,市辖区,老城区,西工区,瀍河回族区,涧西区,吉利区,洛龙区,孟津县,新安县,栾川县,嵩县,汝阳县,宜阳县,洛宁县,伊川县,偃师市|平顶山市,市辖区,新华区,卫东区,石龙区,湛河区,宝丰县,叶县,鲁山县,郏县,舞钢市,汝州市|安阳市,市辖区,文峰区,北关区,殷都区,龙安区,安阳县,汤阴县,滑县,内黄县,林州市|鹤壁市,市辖区,鹤山区,山城区,淇滨区,浚县,淇县|新乡市,市辖区,红旗区,卫滨区,凤泉区,牧野区,新乡县,获嘉县,原阳县,延津县,封丘县,长垣县,卫辉市,辉县市|焦作市,市辖区,解放区,中站区,马村区,山阳区,修武县,博爱县,武陟县,温县,沁阳市,孟州市|濮阳市,市辖区,华龙区,清丰县,南乐县,范县,台前县,濮阳县|许昌市,市辖区,魏都区,许昌县,鄢陵县,襄城县,禹州市,长葛市|漯河市,市辖区,源汇区,郾城区,召陵区,舞阳县,临颍县|三门峡市,市辖区,湖滨区,渑池县,陕县,卢氏县,义马市,灵宝市|南阳市,市辖区,宛城区,卧龙区,南召县,方城县,西峡县,镇平县,内乡县,淅川县,社旗县,唐河县,新野县,桐柏县,邓州市|商丘市,市辖区,梁园区,睢阳区,民权县,睢县,宁陵县,柘城县,虞城县,夏邑县,永城市|信阳市,市辖区,浉河区,平桥区,罗山县,光山县,新县,商城县,固始县,潢川县,淮滨县,息县|周口市,市辖区,川汇区,扶沟县,西华县,商水县,沈丘县,郸城县,淮阳县,太康县,鹿邑县,项城市|驻马店市,市辖区,驿城区,西平县,上蔡县,平舆县,正阳县,确山县,泌阳县,汝南县,遂平县,新蔡县|省直辖县级行政区划,济源市#湖北省$武汉市,市辖区,江岸区,江汉区,硚口区,汉阳区,武昌区,青山区,洪山区,东西湖区,汉南区,蔡甸区,江夏区,黄陂区,新洲区|黄石市,市辖区,黄石港区,西塞山区,下陆区,铁山区,阳新县,大冶市|十堰市,市辖区,茅箭区,张湾区,郧县,郧西县,竹山县,竹溪县,房县,丹江口市|宜昌市,市辖区,西陵区,伍家岗区,点军区,猇亭区,夷陵区,远安县,兴山县,秭归县,长阳土家族自治县,五峰土家族自治县,宜都市,当阳市,枝江市|襄阳市,市辖区,襄城区,樊城区,襄州区,南漳县,谷城县,保康县,老河口市,枣阳市,宜城市|鄂州市,市辖区,梁子湖区,华容区,鄂城区|荆门市,市辖区,东宝区,掇刀区,京山县,沙洋县,钟祥市|孝感市,市辖区,孝南区,孝昌县,大悟县,云梦县,应城市,安陆市,汉川市|荆州市,市辖区,沙市区,荆州区,公安县,监利县,江陵县,石首市,洪湖市,松滋市|黄冈市,市辖区,黄州区,团风县,红安县,罗田县,英山县,浠水县,蕲春县,黄梅县,麻城市,武穴市|咸宁市,市辖区,咸安区,嘉鱼县,通城县,崇阳县,通山县,赤壁市|随州市,市辖区,曾都区,随县,广水市|恩施土家族苗族自治州,恩施市,利川市,建始县,巴东县,宣恩县,咸丰县,来凤县,鹤峰县|省直辖县级行政区划,仙桃市,潜江市,天门市,神农架林区#湖南省$长沙市,市辖区,芙蓉区,天心区,岳麓区,开福区,雨花区,望城区,长沙县,宁乡县,浏阳市|株洲市,市辖区,荷塘区,芦淞区,石峰区,天元区,株洲县,攸县,茶陵县,炎陵县,醴陵市|湘潭市,市辖区,雨湖区,岳塘区,湘潭县,湘乡市,韶山市|衡阳市,市辖区,珠晖区,雁峰区,石鼓区,蒸湘区,南岳区,衡阳县,衡南县,衡山县,衡东县,祁东县,耒阳市,常宁市|邵阳市,市辖区,双清区,大祥区,北塔区,邵东县,新邵县,邵阳县,隆回县,洞口县,绥宁县,新宁县,城步苗族自治县,武冈市|岳阳市,市辖区,岳阳楼区,云溪区,君山区,岳阳县,华容县,湘阴县,平江县,汨罗市,临湘市|常德市,市辖区,武陵区,鼎城区,安乡县,汉寿县,澧县,临澧县,桃源县,石门县,津市市|张家界市,市辖区,永定区,武陵源区,慈利县,桑植县|益阳市,市辖区,资阳区,赫山区,南县,桃江县,安化县,沅江市|郴州市,市辖区,北湖区,苏仙区,桂阳县,宜章县,永兴县,嘉禾县,临武县,汝城县,桂东县,安仁县,资兴市|永州市,市辖区,零陵区,冷水滩区,祁阳县,东安县,双牌县,道县,江永县,宁远县,蓝山县,新田县,江华瑶族自治县|怀化市,市辖区,鹤城区,中方县,沅陵县,辰溪县,溆浦县,会同县,麻阳苗族自治县,新晃侗族自治县,芷江侗族自治县,靖州苗族侗族自治县,通道侗族自治县,洪江市|娄底市,市辖区,娄星区,双峰县,新化县,冷水江市,涟源市|湘西土家族苗族自治州,吉首市,泸溪县,凤凰县,花垣县,保靖县,古丈县,永顺县,龙山县#广东省$广州市,市辖区,荔湾区,越秀区,海珠区,天河区,白云区,黄埔区,番禺区,花都区,南沙区,萝岗区,增城市,从化市|韶关市,市辖区,武江区,浈江区,曲江区,始兴县,仁化县,翁源县,乳源瑶族自治县,新丰县,乐昌市,南雄市|深圳市,市辖区,罗湖区,福田区,南山区,宝安区,龙岗区,盐田区|珠海市,市辖区,香洲区,斗门区,金湾区|汕头市,市辖区,龙湖区,金平区,濠江区,潮阳区,潮南区,澄海区,南澳县|佛山市,市辖区,禅城区,南海区,顺德区,三水区,高明区|江门市,市辖区,蓬江区,江海区,新会区,台山市,开平市,鹤山市,恩平市|湛江市,市辖区,赤坎区,霞山区,坡头区,麻章区,遂溪县,徐闻县,廉江市,雷州市,吴川市|茂名市,市辖区,茂南区,茂港区,电白县,高州市,化州市,信宜市|肇庆市,市辖区,端州区,鼎湖区,广宁县,怀集县,封开县,德庆县,高要市,四会市|惠州市,市辖区,惠城区,惠阳区,博罗县,惠东县,龙门县|梅州市,市辖区,梅江区,梅县,大埔县,丰顺县,五华县,平远县,蕉岭县,兴宁市|汕尾市,市辖区,城区,海丰县,陆河县,陆丰市|河源市,市辖区,源城区,紫金县,龙川县,连平县,和平县,东源县|阳江市,市辖区,江城区,阳西县,阳东县,阳春市|清远市,市辖区,清城区,佛冈县,阳山县,连山壮族瑶族自治县,连南瑶族自治县,清新县,英德市,连州市|东莞市|中山市|潮州市,市辖区,湘桥区,潮安县,饶平县|揭阳市,市辖区,榕城区,揭东县,揭西县,惠来县,普宁市|云浮市,市辖区,云城区,新兴县,郁南县,云安县,罗定市#广西壮族自治区$南宁市,市辖区,兴宁区,青秀区,江南区,西乡塘区,良庆区,邕宁区,武鸣县,隆安县,马山县,上林县,宾阳县,横县|柳州市,市辖区,城中区,鱼峰区,柳南区,柳北区,柳江县,柳城县,鹿寨县,融安县,融水苗族自治县,三江侗族自治县|桂林市,市辖区,秀峰区,叠彩区,象山区,七星区,雁山区,阳朔县,临桂县,灵川县,全州县,兴安县,永福县,灌阳县,龙胜各族自治县,资源县,平乐县,荔蒲县,恭城瑶族自治县|梧州市,市辖区,万秀区,蝶山区,长洲区,苍梧县,藤县,蒙山县,岑溪市|北海市,市辖区,海城区,银海区,铁山港区,合浦县|防城港市,市辖区,港口区,防城区,上思县,东兴市|钦州市,市辖区,钦南区,钦北区,灵山县,浦北县|贵港市,市辖区,港北区,港南区,覃塘区,平南县,桂平市|玉林市,市辖区,玉州区,容县,陆川县,博白县,兴业县,北流市|百色市,市辖区,右江区,田阳县,田东县,平果县,德保县,靖西县,那坡县,凌云县,乐业县,田林县,西林县,隆林各族自治县|贺州市,市辖区,八步区,昭平县,钟山县,富川瑶族自治县|河池市,市辖区,金城江区,南丹县,天峨县,凤山县,东兰县,罗城仫佬族自治县,环江毛南族自治县,巴马瑶族自治县,都安瑶族自治县,大化瑶族自治县,宜州市|来宾市,市辖区,兴宾区,忻城县,象州县,武宣县,金秀瑶族自治县,合山市|崇左市,市辖区,江洲区,扶绥县,宁明县,龙州县,大新县,天等县,凭祥市#海南省$海口市,市辖区,秀英区,龙华区,琼山区,美兰区|三亚市,市辖区,河东区,河西区,吉阳区,天涯区,海棠区,崖州区|省直辖县级行政区划,五指山市,琼海市,儋州市,文昌市,万宁市,东方市,定安县,屯昌县,澄迈县,临高县,白沙黎族自治县,昌江黎族自治县,乐东黎族自治县,陵水黎族自治县,保亭黎族苗族自治县,琼中黎族苗族自治县,西沙群岛,南沙群岛,中沙群岛的岛礁及其海域#重庆市$市辖区,万州区,涪陵区,渝中区,大渡口区,江北区,沙坪坝区,九龙坡区,南岸区,北碚区,綦江区,大足区,渝北区,巴南区,黔江区,长寿区,江津区,合川区,永川区,南川区|市辖县,潼南县,铜梁县,荣昌县,璧山县,梁平县,城口县,丰都县,垫江县,武隆县,忠县,开县,云阳县,奉节县,巫山县,巫溪县,石柱土家族自治县,秀山土家族苗族自治县,酉阳土家族苗族自治县,彭水苗族土家族自治县#四川省$成都市,市辖区,锦江区,青羊区,金牛区,武侯区,成华区,龙泉驿区,青白江区,新都区,温江区,金堂县,双流县,郫县,大邑县,蒲江县,新津县,都江堰市,彭州市,邛崃市,崇州市|自贡市,市辖区,自流井区,贡井区,大安区,沿滩区,荣县,富顺县|攀枝花市,市辖区,东区,西区,仁和区,米易县,盐边县|泸州市,市辖区,江阳区,纳溪区,龙马潭区,泸县,合江县,叙永县,古蔺县|德阳市,市辖区,旌阳区,中江县,罗江县,广汉市,什邡市,绵竹市|绵阳市,市辖区,涪城区,游仙区,三台县,盐亭县,安县,梓潼县,北川羌族自治县,平武县,江油市|广元市,市辖区,利州区,元坝区,朝天区,旺苍县,青川县,剑阁县,苍溪县|遂宁市,市辖区,船山区,安居区,蓬溪县,射洪县,大英县|内江市,市辖区,市中区,东兴区,威远县,资中县,隆昌县|乐山市,市辖区,市中区,沙湾区,五通桥区,金口河区,犍为县,井研县,夹江县,沐川县,峨边彝族自治县,马边彝族自治县,峨眉山市|南充市,市辖区,顺庆区,高坪区,嘉陵区,南部县,营山县,蓬安县,仪陇县,西充县,阆中市|眉山市,市辖区,东坡区,仁寿县,彭山县,洪雅县,丹棱县,青神县|宜宾市,市辖区,翠屏区,南溪区,宜宾县,江安县,长宁县,高县,珙县,筠连县,兴文县,屏山县|广安市,市辖区,广安区,岳池县,武胜县,邻水县,华蓥市|达州市,市辖区,通川区,达县,宣汉县,开江县,大竹县,渠县,万源市|雅安市,市辖区,雨城区,名山县,荥经县,汉源县,石棉县,天全县,芦山县,宝兴县|巴中市,市辖区,巴州区,通江县,南江县,平昌县|资阳市,市辖区,雁江区,安岳县,乐至县,简阳市|阿坝藏族羌族自治州,汶川县,理县,茂县,松潘县,九寨沟县,金川县,小金县,黑水县,马尔康县,壤塘县,阿坝县,若尔盖县,红原县|甘孜藏族自治州,康定县,泸定县,丹巴县,九龙县,雅江县,道孚县,炉霍县,甘孜县,新龙县,德格县,白玉县,石渠县,色达县,理塘县,巴塘县,乡城县,稻城县,得荣县|凉山彝族自治州,西昌市,木里藏族自治县,盐源县,德昌县,会理县,会东县,宁南县,普格县,布拖县,金阳县,昭觉县,喜德县,冕宁县,越西县,甘洛县,美姑县,雷波县#贵州省$贵阳市,市辖区,南明区,云岩区,花溪区,乌当区,白云区,小河区,开阳县,息烽县,修文县,清镇市|六盘水市,钟山区,六枝特区,水城县,盘县|遵义市,市辖区,红花岗区,汇川区,遵义县,桐梓县,绥阳县,正安县,道真仡佬族苗族自治县,务川仡佬族苗族自治县,凤冈县,湄潭县,余庆县,习水县,赤水市,仁怀市|安顺市,市辖区,西秀区,平坝县,普定县,镇宁布依族苗族自治县,关岭布依族苗族自治县,紫云苗族布依族自治县|毕节市,市辖区,七星关区,大方县,黔西县,金沙县,织金县,纳雍县,威宁彝族回族苗族自治县,赫章县|铜仁市,市辖区,碧江区,万山区,江口县,玉屏侗族自治县,石阡县,思南县,印江土家族苗族自治县,德江县,沿河土家族自治县,松桃苗族自治县|黔西南布依族苗族自治州,兴义市,兴仁县,普安县,晴隆县,贞丰县,望谟县,册亨县,安龙县|黔东南苗族侗族自治州,凯里市,黄平县,施秉县,三穗县,镇远县,岑巩县,天柱县,锦屏县,剑河县,台江县,黎平县,榕江县,从江县,雷山县,麻江县,丹寨县|黔南布依族苗族自治州,都匀市,福泉市,荔波县,贵定县,瓮安县,独山县,平塘县,罗甸县,长顺县,龙里县,惠水县,三都水族自治县#云南省$昆明市,市辖区,五华区,盘龙区,官渡区,西山区,东川区,呈贡区,晋宁县,富民县,宜良县,石林彝族自治县,嵩明县,禄劝彝族苗族自治县,寻甸回族彝族自治县,安宁市|曲靖市,市辖区,麒麟区,马龙县,陆良县,师宗县,罗平县,富源县,会泽县,沾益县,宣威市|玉溪市,市辖区,红塔区,江川县,澄江县,通海县,华宁县,易门县,峨山彝族自治县,新平彝族傣族自治县,元江哈尼族彝族傣族自治县|保山市,市辖区,隆阳区,施甸县,腾冲县,龙陵县,昌宁县|昭通市,市辖区,昭阳区,鲁甸县,巧家县,盐津县,大关县,永善县,绥江县,镇雄县,彝良县,威信县,水富县|丽江市,市辖区,古城区,玉龙纳西族自治县,永胜县,华坪县,宁蒗彝族自治县|普洱市,市辖区,思茅区,宁洱哈尼族彝族自治县,墨江哈尼族自治县,景东彝族自治县,景谷傣族彝族自治县,镇沅彝族哈尼族拉祜族自治县,江城哈尼族彝族自治县,孟连傣族拉祜族佤族自治县,澜沧拉祜族自治县,西盟佤族自治县|临沧市,市辖区,临翔区,凤庆县,云县,永德县,镇康县,双江拉祜族佤族布朗族傣族自治县,耿马傣族佤族自治县,沧源佤族自治县|楚雄彝族自治州,楚雄市,双柏县,牟定县,南华县,姚安县,大姚县,永仁县,元谋县,武定县,禄丰县|红河哈尼族彝族自治州,个旧市,开远市,蒙自市,屏边苗族自治县,建水县,石屏县,弥勒县,泸西县,元阳县,红河县,金平苗族瑶族傣族自治县,绿春县,河口瑶族自治县|文山壮族苗族自治州,文山市,砚山县,西畴县,麻栗坡县,马关县,丘北县,广南县,富宁县|西双版纳傣族自治州,景洪市,勐海县,勐腊县|大理白族自治州,大理市,漾濞彝族自治县,祥云县,宾川县,弥渡县,南涧彝族自治县,巍山彝族回族自治县,永平县,云龙县,洱源县,剑川县,鹤庆县|德宏傣族景颇族自治州,瑞丽市,芒市,梁河县,盈江县,陇川县|怒江傈僳族自治州,泸水县,福贡县,贡山独龙族怒族自治县,兰坪白族普米族自治县|迪庆藏族自治州,香格里拉县,德钦县,维西傈僳族自治县#西藏自治区$拉萨市,市辖区,城关区,林周县,当雄县,尼木县,曲水县,堆龙德庆县,达孜县,墨竹工卡县|昌都地区,昌都县,江达县,贡觉县,类乌齐县,丁青县,察雅县,八宿县,左贡县,芒康县,洛隆县,边坝县|山南地区,乃东县,扎囊县,贡嘎县,桑日县,琼结县,曲松县,措美县,洛扎县,加查县,隆子县,错那县,浪卡子县|日喀则地区,日喀则市,南木林县,江孜县,定日县,萨迦县,拉孜县,昂仁县,谢通门县,白朗县,仁布县,康马县,定结县,仲巴县,亚东县,吉隆县,聂拉木县,萨嘎县,岗巴县|那曲地区,那曲县,嘉黎县,比如县,聂荣县,安多县,申扎县,索县,班戈县,巴青县,尼玛县|阿里地区,普兰县,札达县,噶尔县,日土县,革吉县,改则县,措勤县|林芝地区,林芝县,工布江达县,米林县,墨脱县,波密县,察隅县,朗县#陕西省$西安市,市辖区,新城区,碑林区,莲湖区,灞桥区,未央区,雁塔区,阎良区,临潼区,长安区,蓝田县,周至县,户县,高陵县|铜川市,市辖区,王益区,印台区,耀州区,宜君县|宝鸡市,市辖区,渭滨区,金台区,陈仓区,凤翔县,岐山县,扶风县,眉县,陇县,千阳县,麟游县,凤县,太白县|咸阳市,市辖区,秦都区,杨陵区,渭城区,三原县,泾阳县,乾县,礼泉县,永寿县,彬县,长武县,旬邑县,淳化县,武功县,兴平市|渭南市,市辖区,临渭区,华县,潼关县,大荔县,合阳县,澄城县,蒲城县,白水县,富平县,韩城市,华阴市|延安市,市辖区,宝塔区,延长县,延川县,子长县,安塞县,志丹县,吴起县,甘泉县,富县,洛川县,宜川县,黄龙县,黄陵县|汉中市,市辖区,汉台区,南郑县,城固县,洋县,西乡县,勉县,宁强县,略阳县,镇巴县,留坝县,佛坪县|榆林市,市辖区,榆阳区,神木县,府谷县,横山县,靖边县,定边县,绥德县,米脂县,佳县,吴堡县,清涧县,子洲县|安康市,市辖区,汉滨区,汉阴县,石泉县,宁陕县,紫阳县,岚皋县,平利县,镇坪县,旬阳县,白河县|商洛市,市辖区,商州区,洛南县,丹凤县,商南县,山阳县,镇安县,柞水县#甘肃省$兰州市,市辖区,城关区,七里河区,西固区,安宁区,红古区,永登县,皋兰县,榆中县|嘉峪关市,市辖区|金昌市,市辖区,金川区,永昌县|白银市,市辖区,白银区,平川区,靖远县,会宁县,景泰县|天水市,市辖区,秦州区,麦积区,清水县,秦安县,甘谷县,武山县,张家川回族自治县|武威市,市辖区,凉州区,民勤县,古浪县,天祝藏族自治县|张掖市,市辖区,甘州区,肃南裕固族自治县,民乐县,临泽县,高台县,山丹县|平凉市,市辖区,崆峒区,泾川县,灵台县,崇信县,华亭县,庄浪县,静宁县|酒泉市,市辖区,肃州区,金塔县,瓜州县,肃北蒙古族自治县,阿克塞哈萨克族自治县,玉门市,敦煌市|庆阳市,市辖区,西峰区,庆城县,环县,华池县,合水县,正宁县,宁县,镇原县|定西市,市辖区,安定区,通渭县,陇西县,渭源县,临洮县,漳县,岷县|陇南市,市辖区,武都区,成县,文县,宕昌县,康县,西和县,礼县,徽县,两当县|临夏回族自治州,临夏市,临夏县,康乐县,永靖县,广河县,和政县,东乡族自治县,积石山保安族东乡族撒拉族自治县|甘南藏族自治州,合作市,临潭县,卓尼县,舟曲县,迭部县,玛曲县,碌曲县,夏河县#青海省$西宁市,市辖区,城东区,城中区,城西区,城北区,大通回族土族自治县,湟中县,湟源县|海东地区,平安县,民和回族土族自治县,乐都县,互助土族自治县,化隆回族自治县,循化撒拉族自治县|海北藏族自治州,门源回族自治县,祁连县,海晏县,刚察县|黄南藏族自治州,同仁县,尖扎县,泽库县,河南蒙古族自治县|海南藏族自治州,共和县,同德县,贵德县,兴海县,贵南县|果洛藏族自治州,玛沁县,班玛县,甘德县,达日县,久治县,玛多县|玉树藏族自治州,玉树县,杂多县,称多县,治多县,囊谦县,曲麻莱县|海西蒙古族藏族自治州,格尔木市,德令哈市,乌兰县,都兰县,天峻县#宁夏回族自治区$银川市,市辖区,兴庆区,西夏区,金凤区,永宁县,贺兰县,灵武市|石嘴山市,市辖区,大武口区,惠农区,平罗县|吴忠市,市辖区,利通区,红寺堡区,盐池县,同心县,青铜峡市|固原市,市辖区,原州区,西吉县,隆德县,泾源县,彭阳县|中卫市,市辖区,沙坡头区,中宁县,海原县#新疆维吾尔自治区$乌鲁木齐市,市辖区,天山区,沙依巴克区,新市区,水磨沟区,头屯河区,达坂城区,米东区,乌鲁木齐县|克拉玛依市,市辖区,独山子区,克拉玛依区,白碱滩区,乌尔禾区|吐鲁番地区,吐鲁番市,鄯善县,托克逊县|哈密地区,哈密市,巴里坤哈萨克自治县,伊吾县|昌吉回族自治州,昌吉市,阜康市,呼图壁县,玛纳斯县,奇台县,吉木萨尔县,木垒哈萨克自治县|博尔塔拉蒙古自治州,博乐市,精河县,温泉县|巴音郭楞蒙古自治州,库尔勒市,轮台县,尉犁县,若羌县,且末县,焉耆回族自治县,和静县,和硕县,博湖县|阿克苏地区,阿克苏市,温宿县,库车县,沙雅县,新和县,拜城县,乌什县,阿瓦提县,柯坪县|克孜勒苏柯尔克孜自治州,阿图什市,阿克陶县,阿合奇县,乌恰县|喀什地区,喀什市,疏附县,疏勒县,英吉沙县,泽普县,莎车县,叶城县,麦盖提县,岳普湖县,伽师县,巴楚县,塔什库尔干塔吉克自治县|和田地区,和田市,和田县,墨玉县,皮山县,洛浦县,策勒县,于田县,民丰县|伊犁哈萨克自治州,伊宁市,奎屯市,伊宁县,察布查尔锡伯自治县,霍城县,巩留县,新源县,昭苏县,特克斯县,尼勒克县|塔城地区,塔城市,乌苏市,额敏县,沙湾县,托里县,裕民县,和布克赛尔蒙古自治县|阿勒泰地区,阿勒泰市,布尔津县,富蕴县,福海县,哈巴河县,青河县,吉木乃县|自治区直辖县级行政区划,石河子市,阿拉尔市,图木舒克市,五家渠市#香港特别行政区$香港,香港特别行政区#澳门特别行政区$澳门,澳门特别行政区#台湾省$台北市,中正区,大同区,中山区,松山区,大安区,万华区,信义区,士林区,北投区,内湖区,南港区,文山区|高雄市,新兴区,前金区,芩雅区,盐埕区,鼓山区,旗津区,前镇区,三民区,左营区,楠梓区,小港区|基隆市,仁爱区,信义区,中正区,中山区,安乐区,暖暖区,七堵区|台中市,中区,东区,南区,西区,北区,北屯区,西屯区,南屯区|台南市,中西区,东区,南区,北区,安平区,安南区|新竹市,东区,北区,香山区|嘉义市,东区,西区|县,台北县(板桥市),宜兰县(宜兰市),新竹县(竹北市),桃园县(桃园市),苗栗县(苗栗市),台中县(丰原市),彰化县(彰化市),南投县(南投市),嘉义县(太保市),云林县(斗六市),台南县(新营市),高雄县(凤山市),屏东县(屏东市),台东县(台东市),花莲县(花莲市),澎湖县(马公市)#其它$亚洲,阿富汗,巴林,孟加拉国,不丹,文莱,缅甸,塞浦路斯,印度,印度尼西亚,伊朗,伊拉克,日本,约旦,朝鲜,科威特,老挝,马尔代夫,黎巴嫩,马来西亚,以色列,蒙古,尼泊尔,阿曼,巴基斯坦,巴勒斯坦,菲律宾,沙特阿拉伯,新加坡,斯里兰卡,叙利亚,泰国,柬埔寨,土耳其,阿联酋,越南,也门,韩国,中国,中国香港,中国澳门,中国台湾|非洲,阿尔及利亚,安哥拉,厄里特里亚,法罗群鸟,加那利群岛(西)(拉斯帕尔马斯),贝宁,博茨瓦纳,布基纳法索,布隆迪,喀麦隆,加那利群岛(西)(圣克鲁斯),佛得角,中非,乍得,科摩罗,刚果,吉布提,埃及,埃塞俄比亚,赤道几内亚,加蓬,冈比亚,加纳,几内亚,南非,几内亚比绍,科特迪瓦,肯尼亚,莱索托,利比里亚,利比亚,马达加斯加,马拉维,马里,毛里塔尼亚,毛里求斯,摩洛哥,莫桑比克,尼日尔,尼日利亚,留尼旺岛,卢旺达,塞内加尔,塞舌尔,塞拉利昂,索马里,苏丹,斯威士兰,坦桑尼亚,圣赤勒拿,多哥,突尼斯,乌干达,扎伊尔,赞比亚,津巴布韦,纳米比亚,迪戈加西亚,桑给巴尔,马约特岛,圣多美和普林西比|欧洲,阿尔巴尼亚,安道尔,奥地利,比利时,保加利亚,捷克,丹麦,芬兰,法国,德国,直布罗陀(英),希腊,匈牙利,冰岛,爱尔兰,意大利,列支敦士登,斯洛伐克,卢森堡,马耳他,摩纳哥,荷兰,挪威,波兰,葡萄牙,马其顿,罗马尼亚,南斯拉夫,圣马力诺,西班牙,瑞典,瑞士,英国,科罗地亚,斯洛文尼亚,梵蒂冈,波斯尼亚和塞哥维那,俄罗斯联邦,亚美尼亚共和国,白俄罗斯共和国,格鲁吉亚共和国,哈萨克斯坦共和国,吉尔吉斯坦共和国,乌兹别克斯坦共和国,塔吉克斯坦共和国,土库曼斯坦共和国,乌克兰,立陶宛,拉脱维亚,爱沙尼亚,摩尔多瓦,阿塞拜疆|美洲,安圭拉岛,安提瓜和巴布达,阿根廷,阿鲁巴岛,阿森松,巴哈马,巴巴多斯,伯利兹,百慕大群岛,玻利维亚,巴西,加拿大,开曼群岛,智利,哥伦比亚,多米尼加联邦,哥斯达黎加,古巴,多米尼加共和国,厄瓜多尔,萨尔瓦多,法属圭亚那,格林纳达,危地马拉,圭亚那,海地,洪都拉斯,牙买加,马提尼克(法),墨西哥,蒙特塞拉特岛,荷属安的列斯群岛,尼加拉瓜,巴拿马,巴拉圭,秘鲁,波多黎哥,圣皮埃尔岛密克隆岛(法),圣克里斯托弗和尼维斯,圣卢西亚,福克兰群岛,维尔京群岛(英),圣文森特岛(英),维尔京群岛(美),苏里南,特立尼达和多巴哥,乌拉圭,美国,委内瑞拉,格陵兰岛,特克斯和凯科斯群岛,瓜多罗普|大洋洲,澳大利亚,科克群岛,斐济,法属波里尼西亚、塔希提,瓦努阿图,关岛,基里巴斯,马里亚纳群岛,中途岛,瑙鲁,新咯里多尼亚群岛,新西兰,巴布亚新几内亚,东萨摩亚,西萨摩亚,所罗门群岛,汤加,对诞岛,威克岛,科科斯岛,夏威夷,诺福克岛,帕劳,纽埃岛,图瓦卢,托克鲁,密克罗尼西亚,马绍尔群岛,瓦里斯加富士那群岛";
PCAD = "安徽省$安庆市,大观区,怀宁县,潜山县,宿松县,太湖县,桐城市,望江县,宜秀区,迎江区,岳西县|蚌埠市,蚌山区,固镇县,怀远县,淮上区,龙子湖区,五河县,禹会区|亳州市,利辛县,蒙城县,谯城区,涡阳县|池州市,东至县,贵池区,青阳县,石台县|滁州市,定远县,凤阳县,来安县,琅琊区,明光市,南谯区,全椒县,天长市|阜阳市,阜南县,界首市,临泉县,太和县,颍东区,颍泉区,颍上县,颍州区|合肥市,包河区,长丰县,巢湖市,肥东县,肥西县,庐江县,庐阳区,蜀山区,瑶海区|淮北市,杜集区,烈山区,濉溪县,相山区|淮南市,八公山区,大通区,凤台县,潘集区,寿县,田家庵区,谢家集区|黄山市,黄山区,徽州区,祁门县,屯溪区,歙县,休宁县,黟县|六安市,霍邱县,霍山县,金安区,金寨县,舒城县,叶集区,裕安区|马鞍山市,博望区,当涂县,含山县,和县,花山区,雨山区|宿州市,砀山县,灵璧县,泗县,萧县,埇桥区|铜陵市,枞阳县,郊区,铜官区,义安区|芜湖市,繁昌县,镜湖区,鸠江区,南陵县,三山区,无为县,芜湖县,弋江区|宣城市,广德县,绩溪县,泾县,旌德县,郎溪县,宁国市,宣州区#澳门特别行政区$#北京市$市辖区,昌平区,朝阳区,大兴区,东城区,房山区,丰台区,海淀区,怀柔区,门头沟区,密云区,平谷区,石景山区,顺义区,通州区,西城区,延庆区#重庆市$市辖区,巴南区,北碚区,璧山区,长寿区,大渡口区,大足区,涪陵区,合川区,江北区,江津区,九龙坡区,开州区,南岸区,南川区,綦江区,黔江区,荣昌区,沙坪坝区,铜梁区,潼南区,万州区,永川区,渝北区,渝中区|县,城口县,垫江县,丰都县,奉节县,梁平县,彭水苗族土家族自治县,石柱土家族自治县,巫山县,巫溪县,武隆县,秀山土家族苗族自治县,酉阳土家族苗族自治县,云阳县,忠县#福建省$福州市,仓山区,长乐市,福清市,鼓楼区,晋安区,连江县,罗源县,马尾区,闽侯县,闽清县,平潭县,台江区,永泰县|龙岩市,长汀县,连城县,上杭县,武平县,新罗区,永定区,漳平市|南平市,光泽县,建瓯市,建阳区,浦城县,邵武市,顺昌县,松溪县,武夷山市,延平区,政和县|宁德市,福安市,福鼎市,古田县,蕉城区,屏南县,寿宁县,霞浦县,柘荣县,周宁县|莆田市,城厢区,涵江区,荔城区,仙游县,秀屿区|泉州市,安溪县,德化县,丰泽区,惠安县,金门县,晋江市,鲤城区,洛江区,南安市,泉港区,石狮市,永春县|三明市,大田县,建宁县,将乐县,梅列区,明溪县,宁化县,清流县,三元区,沙县,泰宁县,永安市,尤溪县|厦门市,海沧区,湖里区,集美区,思明区,同安区,翔安区|漳州市,长泰县,东山县,华安县,龙海市,龙文区,南靖县,平和县,芗城区,云霄县,漳浦县,诏安县#甘肃省$白银市,白银区,会宁县,景泰县,靖远县,平川区|定西市,安定区,临洮县,陇西县,岷县,通渭县,渭源县,漳县|甘南藏族自治州,迭部县,合作市,临潭县,碌曲县,玛曲县,夏河县,舟曲县,卓尼县|嘉峪关市,长城区,镜铁区,文殊镇,新城镇,雄关区,峪泉镇|金昌市,金川区,永昌县|酒泉市,阿克塞哈萨克族自治县,敦煌市,瓜州县,金塔县,肃北蒙古族自治县,肃州区,玉门市|兰州市,安宁区,城关区,皋兰县,红古区,七里河区,西固区,永登县,榆中县|临夏回族自治州,东乡族自治县,广河县,和政县,积石山保安族东乡族撒拉族自治县,康乐县,临夏市,临夏县,永靖县|陇南市,成县,宕昌县,徽县,康县,礼县,两当县,文县,武都区,西和县|平凉市,崇信县,华亭县,泾川县,静宁县,崆峒区,灵台县,庄浪县|庆阳市,合水县,华池县,环县,宁县,庆城县,西峰区,镇原县,正宁县|天水市,甘谷县,麦积区,秦安县,秦州区,清水县,武山县,张家川回族自治县|武威市,古浪县,凉州区,民勤县,天祝藏族自治县|张掖市,甘州区,高台县,临泽县,民乐县,山丹县,肃南裕固族自治县#广东省$潮州市,潮安区,饶平县,湘桥区|东莞市,茶山镇,长安镇,常平镇,大朗镇,大岭山镇,道滘镇,东城街道办事处,东坑镇,东莞生态园,凤岗镇,高埗镇,横沥镇,洪梅镇,厚街镇,虎门港管委会,虎门镇,黄江镇,寮步镇,麻涌镇,南城街道办事处,企石镇,桥头镇,清溪镇,沙田镇,石碣镇,石龙镇,石排镇,松山湖管委会,塘厦镇,莞城街道办事处,万江街道办事处,望牛墩镇,谢岗镇,樟木头镇,中堂镇|佛山市,禅城区,高明区,南海区,三水区,顺德区|广州市,白云区,从化区,番禺区,海珠区,花都区,黄埔区,荔湾区,南沙区,天河区,越秀区,增城区|河源市,东源县,和平县,连平县,龙川县,源城区,紫金县|惠州市,博罗县,惠城区,惠东县,惠阳区,龙门县|江门市,恩平市,鹤山市,江海区,开平市,蓬江区,台山市,新会区|揭阳市,惠来县,揭东区,揭西县,普宁市,榕城区|茂名市,电白区,高州市,化州市,茂南区,信宜市|梅州市,大埔县,丰顺县,蕉岭县,梅江区,梅县区,平远县,五华县,兴宁市|清远市,佛冈县,连南瑶族自治县,连山壮族瑶族自治县,连州市,清城区,清新区,阳山县,英德市|汕头市,潮南区,潮阳区,澄海区,濠江区,金平区,龙湖区,南澳县|汕尾市,城区,海丰县,陆丰市,陆河县|韶关市,乐昌市,南雄市,曲江区,仁化县,乳源瑶族自治县,始兴县,翁源县,武江区,新丰县,浈江区|深圳市,宝安区,福田区,龙岗区,罗湖区,南山区,盐田区|阳江市,江城区,阳春市,阳东区,阳西县|云浮市,罗定市,新兴县,郁南县,云安区,云城区|湛江市,赤坎区,雷州市,廉江市,麻章区,坡头区,遂溪县,吴川市,霞山区,徐闻县|肇庆市,德庆县,鼎湖区,端州区,封开县,高要区,广宁县,怀集县,四会市|中山市,板芙镇,大涌镇,东凤镇,东区街道办事处,东升镇,阜沙镇,港口镇,古镇镇,横栏镇,黄圃镇,火炬开发区街道办事处,民众镇,南朗镇,南区街道办事处,南头镇,三角镇,三乡镇,沙溪镇,神湾镇,石岐区街道办事处,坦洲镇,五桂山街道办事处,西区街道办事处,小榄镇|珠海市,斗门区,金湾区,香洲区#广西壮族自治区$百色市,德保县,靖西市,乐业县,凌云县,隆林各族自治县,那坡县,平果县,田东县,田林县,田阳县,西林县,右江区|北海市,海城区,合浦县,铁山港区,银海区|崇左市,大新县,扶绥县,江州区,龙州县,宁明县,凭祥市,天等县|防城港市,东兴市,防城区,港口区,上思县|贵港市,港北区,港南区,桂平市,平南县,覃塘区|桂林市,叠彩区,恭城瑶族自治县,灌阳县,荔浦县,临桂区,灵川县,龙胜各族自治县,平乐县,七星区,全州县,象山区,兴安县,秀峰区,雁山区,阳朔县,永福县,资源县|河池市,巴马瑶族自治县,大化瑶族自治县,东兰县,都安瑶族自治县,凤山县,环江毛南族自治县,金城江区,罗城仫佬族自治县,南丹县,天峨县,宜州市|贺州市,八步区,富川瑶族自治县,平桂区,昭平县,钟山县|来宾市,合山市,金秀瑶族自治县,武宣县,象州县,忻城县,兴宾区|柳州市,城中区,柳北区,柳城县,柳江区,柳南区,鹿寨县,融安县,融水苗族自治县,三江侗族自治县,鱼峰区|南宁市,宾阳县,横县,江南区,良庆区,隆安县,马山县,青秀区,上林县,武鸣区,西乡塘区,兴宁区,邕宁区|钦州市,灵山县,浦北县,钦北区,钦南区|梧州市,苍梧县,岑溪市,长洲区,龙圩区,蒙山县,藤县,万秀区|玉林市,北流市,博白县,福绵区,陆川县,容县,兴业县,玉州区#贵州省$安顺市,关岭布依族苗族自治县,平坝区,普定县,西秀区,镇宁布依族苗族自治县,紫云苗族布依族自治县|毕节市,大方县,赫章县,金沙县,纳雍县,七星关区,黔西县,威宁彝族回族苗族自治县,织金县|贵阳市,白云区,观山湖区,花溪区,开阳县,南明区,清镇市,乌当区,息烽县,修文县,云岩区|六盘水市,六枝特区,盘县,水城县,钟山区|黔东南苗族侗族自治州,岑巩县,从江县,丹寨县,黄平县,剑河县,锦屏县,凯里市,雷山县,黎平县,麻江县,榕江县,三穗县,施秉县,台江县,天柱县,镇远县|黔南布依族苗族自治州,长顺县,都匀市,独山县,福泉市,贵定县,惠水县,荔波县,龙里县,罗甸县,平塘县,三都水族自治县,瓮安县|黔西南布依族苗族自治州,安龙县,册亨县,普安县,晴隆县,望谟县,兴仁县,兴义市,贞丰县|铜仁市,碧江区,德江县,江口县,石阡县,思南县,松桃苗族自治县,万山区,沿河土家族自治县,印江土家族苗族自治县,玉屏侗族自治县|遵义市,播州区,赤水市,道真仡佬族苗族自治县,凤冈县,红花岗区,汇川区,湄潭县,仁怀市,绥阳县,桐梓县,务川仡佬族苗族自治县,习水县,余庆县,正安县#海南省$儋州市,白马井镇,大成镇,东成镇,峨蔓镇,光村镇,国营八一农场,国营蓝洋农场,国营西联农场,国营西培农场,海头镇,和庆镇,华南热作学院,兰洋镇,木棠镇,那大镇,南丰镇,排浦镇,三都镇,王五镇,新州镇,雅星镇,洋浦经济开发区,中和镇|海口市,龙华区,美兰区,琼山区,秀英区|三沙市,南沙群岛,西沙群岛,中沙群岛的岛礁及其海域|三亚市,海棠区,吉阳区,天涯区,崖州区|省直辖县级行政区划,白沙黎族自治县,保亭黎族苗族自治县,昌江黎族自治县,澄迈县,定安县,东方市,乐东黎族自治县,临高县,陵水黎族自治县,琼海市,琼中黎族苗族自治县,屯昌县,万宁市,文昌市,五指山市#河北省$保定市,安国市,安新县,博野县,定兴县,阜平县,高碑店市,高阳县,竞秀区,涞水县,涞源县,蠡县,莲池区,满城区,清苑区,曲阳县,容城县,顺平县,唐县,望都县,雄县,徐水区,易县,涿州市|沧州市,泊头市,沧县,东光县,海兴县,河间市,黄骅市,孟村回族自治县,南皮县,青县,任丘市,肃宁县,吴桥县,献县,新华区,盐山县,运河区|承德市,承德县,丰宁满族自治县,宽城满族自治县,隆化县,滦平县,平泉县,双滦区,双桥区,围场满族蒙古族自治县,兴隆县,鹰手营子矿区|邯郸市,成安县,磁县,丛台区,大名县,肥乡县,峰峰矿区,复兴区,馆陶县,广平县,邯郸县,邯山区,鸡泽县,临漳县,邱县,曲周县,涉县,魏县,武安市,永年县|衡水市,安平县,阜城县,故城县,冀州区,景县,饶阳县,深州市,桃城区,武强县,武邑县,枣强县|廊坊市,安次区,霸州市,大厂回族自治县,大城县,固安县,广阳区,三河市,文安县,香河县,永清县|秦皇岛市,北戴河区,昌黎县,抚宁区,海港区,卢龙县,青龙满族自治县,山海关区|省直辖县级行政区划,定州市,辛集市|石家庄市,长安区,高邑县,藁城区,晋州市,井陉矿区,井陉县,灵寿县,鹿泉区,栾城区,平山县,桥西区,深泽县,无极县,新华区,新乐市,行唐县,裕华区,元氏县,赞皇县,赵县,正定县|唐山市,曹妃甸区,丰南区,丰润区,古冶区,开平区,乐亭县,路北区,路南区,滦南县,滦县,迁安市,迁西县,玉田县,遵化市|邢台市,柏乡县,广宗县,巨鹿县,临城县,临西县,隆尧县,内丘县,南宫市,南和县,宁晋县,平乡县,桥东区,桥西区,清河县,任县,沙河市,威县,新河县,邢台县|张家口市,赤城县,崇礼区,沽源县,怀安县,怀来县,康保县,桥东区,桥西区,尚义县,万全区,蔚县,下花园区,宣化区,阳原县,张北县,涿鹿县#河南省$安阳市,安阳县,北关区,滑县,林州市,龙安区,内黄县,汤阴县,文峰区,殷都区|鹤壁市,鹤山区,浚县,淇滨区,淇县,山城区|焦作市,博爱县,解放区,马村区,孟州市,沁阳市,山阳区,温县,武陟县,修武县,中站区|开封市,鼓楼区,金明区,兰考县,龙亭区,杞县,顺河回族区,通许县,尉氏县,祥符区,禹王台区|洛阳市,瀍河回族区,吉利区,涧西区,老城区,栾川县,洛龙区,洛宁县,孟津县,汝阳县,嵩县,西工区,新安县,偃师市,伊川县,宜阳县|漯河市,临颍县,舞阳县,郾城区,源汇区,召陵区|南阳市,邓州市,方城县,内乡县,南召县,社旗县,唐河县,桐柏县,宛城区,卧龙区,西峡县,淅川县,新野县,镇平县|平顶山市,宝丰县,郏县,鲁山县,汝州市,石龙区,卫东区,舞钢市,新华区,叶县,湛河区|濮阳市,范县,华龙区,南乐县,濮阳县,清丰县,台前县|三门峡市,湖滨区,灵宝市,卢氏县,陕州区,渑池县,义马市|商丘市,梁园区,民权县,宁陵县,睢县,睢阳区,夏邑县,永城市,虞城县,柘城县|省直辖县级行政区划,济源市|新乡市,长垣县,封丘县,凤泉区,红旗区,辉县市,获嘉县,牧野区,卫滨区,卫辉市,新乡县,延津县,原阳县|信阳市,固始县,光山县,淮滨县,潢川县,罗山县,平桥区,商城县,浉河区,息县,新县|许昌市,长葛市,魏都区,襄城县,许昌县,鄢陵县,禹州市|郑州市,登封市,二七区,巩义市,管城回族区,惠济区,金水区,上街区,新密市,新郑市,荥阳市,中牟县,中原区|周口市,川汇区,郸城县,扶沟县,淮阳县,鹿邑县,商水县,沈丘县,太康县,西华县,项城市|驻马店市,泌阳县,平舆县,确山县,汝南县,上蔡县,遂平县,西平县,新蔡县,驿城区,正阳县#黑龙江省$大庆市,大同区,杜尔伯特蒙古族自治县,红岗区,林甸县,龙凤区,让胡路区,萨尔图区,肇源县,肇州县|大兴安岭地区,呼玛县,漠河县,塔河县|哈尔滨市,阿城区,巴彦县,宾县,道里区,道外区,方正县,呼兰区,木兰县,南岗区,平房区,尚志市,双城区,松北区,通河县,五常市,香坊区,延寿县,依兰县|鹤岗市,东山区,工农区,萝北县,南山区,绥滨县,向阳区,兴安区,兴山区|黑河市,爱辉区,北安市,嫩江县,孙吴县,五大连池市,逊克县|鸡西市,城子河区,滴道区,恒山区,虎林市,鸡东县,鸡冠区,梨树区,麻山区,密山市|佳木斯市,东风区,抚远市,富锦市,桦川县,桦南县,郊区,前进区,汤原县,同江市,向阳区|牡丹江市,爱民区,东安区,东宁市,海林市,林口县,穆棱市,宁安市,绥芬河市,西安区,阳明区|七台河市,勃利县,茄子河区,桃山区,新兴区|齐齐哈尔市,昂昂溪区,拜泉县,富拉尔基区,富裕县,甘南县,建华区,克东县,克山县,龙江县,龙沙区,梅里斯达斡尔族区,讷河市,碾子山区,泰来县,铁锋区,依安县|双鸭山市,宝清县,宝山区,集贤县,尖山区,岭东区,饶河县,四方台区,友谊县|绥化市,安达市,北林区,海伦市,兰西县,明水县,青冈县,庆安县,绥棱县,望奎县,肇东市|伊春市,翠峦区,带岭区,红星区,嘉荫县,金山屯区,美溪区,南岔区,上甘岭区,汤旺河区,铁力市,乌马河区,乌伊岭区,五营区,西林区,新青区,伊春区,友好区#湖北省$鄂州市,鄂城区,华容区,梁子湖区|恩施土家族苗族自治州,巴东县,恩施市,鹤峰县,建始县,来凤县,利川市,咸丰县,宣恩县|黄冈市,红安县,黄梅县,黄州区,罗田县,麻城市,蕲春县,团风县,武穴市,浠水县,英山县|黄石市,大冶市,黄石港区,铁山区,西塞山区,下陆区,阳新县|荆门市,东宝区,掇刀区,京山县,沙洋县,钟祥市|荆州市,公安县,洪湖市,监利县,江陵县,荆州区,沙市区,石首市,松滋市|省直辖县级行政区划,潜江市,神农架林区,天门市,仙桃市|十堰市,丹江口市,房县,茅箭区,郧西县,郧阳区,张湾区,竹山县,竹溪县|随州市,广水市,随县,曾都区|武汉市,蔡甸区,东西湖区,汉南区,汉阳区,洪山区,黄陂区,江岸区,江汉区,江夏区,硚口区,青山区,武昌区,新洲区|咸宁市,赤壁市,崇阳县,嘉鱼县,通城县,通山县,咸安区|襄阳市,保康县,樊城区,谷城县,老河口市,南漳县,襄城区,襄州区,宜城市,枣阳市|孝感市,安陆市,大悟县,汉川市,孝昌县,孝南区,应城市,云梦县|宜昌市,长阳土家族自治县,当阳市,点军区,五峰土家族自治县,伍家岗区,西陵区,猇亭区,兴山县,夷陵区,宜都市,远安县,枝江市,秭归县#湖南省$长沙市,长沙县,芙蓉区,开福区,浏阳市,宁乡县,天心区,望城区,雨花区,岳麓区|常德市,安乡县,鼎城区,汉寿县,津市市,澧县,临澧县,石门县,桃源县,武陵区|郴州市,安仁县,北湖区,桂东县,桂阳县,嘉禾县,临武县,汝城县,苏仙区,宜章县,永兴县,资兴市|衡阳市,常宁市,衡东县,衡南县,衡山县,衡阳县,耒阳市,南岳区,祁东县,石鼓区,雁峰区,蒸湘区,珠晖区|怀化市,辰溪县,鹤城区,洪江市,会同县,靖州苗族侗族自治县,麻阳苗族自治县,通道侗族自治县,新晃侗族自治县,溆浦县,沅陵县,芷江侗族自治县,中方县|娄底市,冷水江市,涟源市,娄星区,双峰县,新化县|邵阳市,北塔区,城步苗族自治县,大祥区,洞口县,隆回县,邵东县,邵阳县,双清区,绥宁县,武冈市,新宁县,新邵县|湘潭市,韶山市,湘潭县,湘乡市,雨湖区,岳塘区|湘西土家族苗族自治州,保靖县,凤凰县,古丈县,花垣县,吉首市,龙山县,泸溪县,永顺县|益阳市,安化县,赫山区,南县,桃江县,沅江市,资阳区|永州市,道县,东安县,江华瑶族自治县,江永县,蓝山县,冷水滩区,零陵区,宁远县,祁阳县,双牌县,新田县|岳阳市,华容县,君山区,临湘市,汨罗市,平江县,湘阴县,岳阳楼区,岳阳县,云溪区|张家界市,慈利县,桑植县,武陵源区,永定区|株洲市,茶陵县,荷塘区,醴陵市,芦淞区,石峰区,天元区,炎陵县,攸县,株洲县#吉林省$白城市,大安市,洮北区,洮南市,通榆县,镇赉县|白山市,长白朝鲜族自治县,抚松县,浑江区,江源区,靖宇县,临江市|长春市,朝阳区,德惠市,二道区,九台区,宽城区,绿园区,南关区,农安县,双阳区,榆树市|吉林市,昌邑区,船营区,丰满区,桦甸市,蛟河市,龙潭区,磐石市,舒兰市,永吉县|辽源市,东丰县,东辽县,龙山区,西安区|四平市,公主岭市,梨树县,双辽市,铁东区,铁西区,伊通满族自治县|松原市,长岭县,扶余市,宁江区,前郭尔罗斯蒙古族自治县,乾安县|通化市,东昌区,二道江区,辉南县,集安市,柳河县,梅河口市,通化县|延边朝鲜族自治州,安图县,敦化市,和龙市,珲春市,龙井市,图们市,汪清县,延吉市#江苏省$常州市,金坛区,溧阳市,天宁区,武进区,新北区,钟楼区|淮安市,洪泽区,淮安区,淮阴区,金湖县,涟水县,清江浦区,盱眙县|连云港市,东海县,赣榆区,灌南县,灌云县,海州区,连云区|南京市,高淳区,鼓楼区,建邺区,江宁区,溧水区,六合区,浦口区,栖霞区,秦淮区,玄武区,雨花台区|南通市,崇川区,港闸区,海安县,海门市,启东市,如东县,如皋市,通州区|苏州市,常熟市,姑苏区,虎丘区,昆山市,太仓市,吴江区,吴中区,相城区,张家港市|宿迁市,沭阳县,泗洪县,泗阳县,宿城区,宿豫区|泰州市,高港区,海陵区,姜堰区,靖江市,泰兴市,兴化市|无锡市,滨湖区,惠山区,江阴市,梁溪区,锡山区,新吴区,宜兴市|徐州市,丰县,鼓楼区,贾汪区,沛县,邳州市,泉山区,睢宁县,铜山区,新沂市,云龙区|盐城市,滨海县,大丰区,东台市,阜宁县,建湖县,射阳县,亭湖区,响水县,盐都区|扬州市,宝应县,高邮市,广陵区,邗江区,江都区,仪征市|镇江市,丹徒区,丹阳市,京口区,句容市,润州区,扬中市#江西省$抚州市,崇仁县,东乡县,广昌县,金溪县,乐安县,黎川县,临川区,南城县,南丰县,宜黄县,资溪县|赣州市,安远县,崇义县,大余县,定南县,赣县,会昌县,龙南县,南康区,宁都县,全南县,瑞金市,上犹县,石城县,信丰县,兴国县,寻乌县,于都县,章贡区|吉安市,安福县,吉安县,吉水县,吉州区,井冈山市,青原区,遂川县,泰和县,万安县,峡江县,新干县,永丰县,永新县|景德镇市,昌江区,浮梁县,乐平市,珠山区|九江市,德安县,都昌县,共青城市,湖口县,九江县,濂溪区,庐山市,彭泽县,瑞昌市,武宁县,修水县,浔阳区,永修县|南昌市,安义县,东湖区,进贤县,南昌县,青山湖区,青云谱区,湾里区,西湖区,新建区|萍乡市,安源区,莲花县,芦溪县,上栗县,湘东区|上饶市,德兴市,广丰区,横峰县,鄱阳县,铅山县,上饶县,万年县,婺源县,信州区,弋阳县,余干县,玉山县|新余市,分宜县,渝水区|宜春市,丰城市,奉新县,高安市,靖安县,上高县,铜鼓县,万载县,宜丰县,袁州区,樟树市|鹰潭市,贵溪市,余江县,月湖区#辽宁省$鞍山市,海城市,立山区,千山区,台安县,铁东区,铁西区,岫岩满族自治县|本溪市,本溪满族自治县,桓仁满族自治县,明山区,南芬区,平山区,溪湖区|朝阳市,北票市,朝阳县,建平县,喀喇沁左翼蒙古族自治县,凌源市,龙城区,双塔区|大连市,长海县,甘井子区,金州区,旅顺口区,普兰店区,沙河口区,瓦房店市,西岗区,中山区,庄河市|丹东市,东港市,凤城市,宽甸满族自治县,元宝区,振安区,振兴区|抚顺市,东洲区,抚顺县,清原满族自治县,顺城区,望花区,新宾满族自治县,新抚区|阜新市,阜新蒙古族自治县,海州区,清河门区,太平区,细河区,新邱区,彰武县|葫芦岛市,建昌县,连山区,龙港区,南票区,绥中县,兴城市|锦州市,北镇市,古塔区,黑山县,凌海市,凌河区,太和区,义县|辽阳市,白塔区,灯塔市,弓长岭区,宏伟区,辽阳县,太子河区,文圣区|盘锦市,大洼区,盘山县,双台子区,兴隆台区|沈阳市,大东区,法库县,和平区,皇姑区,浑南区,康平县,辽中区,沈北新区,沈河区,苏家屯区,铁西区,新民市,于洪区|铁岭市,昌图县,调兵山市,开原市,清河区,铁岭县,西丰县,银州区|营口市,鲅鱼圈区,大石桥市,盖州市,老边区,西市区,站前区#内蒙古自治区$阿拉善盟,阿拉善右旗,阿拉善左旗,额济纳旗|巴彦淖尔市,磴口县,杭锦后旗,临河区,乌拉特后旗,乌拉特前旗,乌拉特中旗,五原县|包头市,白云鄂博矿区,达尔罕茂明安联合旗,东河区,固阳县,九原区,昆都仑区,青山区,石拐区,土默特右旗|赤峰市,阿鲁科尔沁旗,敖汉旗,巴林右旗,巴林左旗,红山区,喀喇沁旗,克什克腾旗,林西县,宁城县,松山区,翁牛特旗,元宝山区|鄂尔多斯市,达拉特旗,东胜区,鄂托克旗,鄂托克前旗,杭锦旗,康巴什区,乌审旗,伊金霍洛旗,准格尔旗|呼和浩特市,和林格尔县,回民区,清水河县,赛罕区,土默特左旗,托克托县,武川县,新城区,玉泉区|呼伦贝尔市,阿荣旗,陈巴尔虎旗,额尔古纳市,鄂伦春自治旗,鄂温克族自治旗,根河市,海拉尔区,满洲里市,莫力达瓦达斡尔族自治旗,新巴尔虎右旗,新巴尔虎左旗,牙克石市,扎赉诺尔区,扎兰屯市|通辽市,霍林郭勒市,开鲁县,科尔沁区,科尔沁左翼后旗,科尔沁左翼中旗,库伦旗,奈曼旗,扎鲁特旗|乌海市,海勃湾区,海南区,乌达区|乌兰察布市,察哈尔右翼后旗,察哈尔右翼前旗,察哈尔右翼中旗,丰镇市,化德县,集宁区,凉城县,商都县,四子王旗,兴和县,卓资县|锡林郭勒盟,阿巴嘎旗,东乌珠穆沁旗,多伦县,二连浩特市,苏尼特右旗,苏尼特左旗,太仆寺旗,西乌珠穆沁旗,锡林浩特市,镶黄旗,正蓝旗,正镶白旗|兴安盟,阿尔山市,科尔沁右翼前旗,科尔沁右翼中旗,突泉县,乌兰浩特市,扎赉特旗#宁夏回族自治区$固原市,泾源县,隆德县,彭阳县,西吉县,原州区|石嘴山市,大武口区,惠农区,平罗县|吴忠市,红寺堡区,利通区,青铜峡市,同心县,盐池县|银川市,贺兰县,金凤区,灵武市,西夏区,兴庆区,永宁县|中卫市,海原县,沙坡头区,中宁县#青海省$果洛藏族自治州,班玛县,达日县,甘德县,久治县,玛多县,玛沁县|海北藏族自治州,刚察县,海晏县,门源回族自治县,祁连县|海东市,互助土族自治县,化隆回族自治县,乐都区,民和回族土族自治县,平安区,循化撒拉族自治县|海南藏族自治州,共和县,贵德县,贵南县,同德县,兴海县|海西蒙古族藏族自治州,德令哈市,都兰县,格尔木市,天峻县,乌兰县|黄南藏族自治州,河南蒙古族自治县,尖扎县,同仁县,泽库县|西宁市,城北区,城东区,城西区,城中区,大通回族土族自治县,湟源县,湟中县|玉树藏族自治州,称多县,囊谦县,曲麻莱县,玉树市,杂多县,治多县#山东省$滨州市,滨城区,博兴县,惠民县,无棣县,阳信县,沾化区,邹平县|德州市,德城区,乐陵市,临邑县,陵城区,宁津县,平原县,齐河县,庆云县,武城县,夏津县,禹城市|东营市,东营区,广饶县,河口区,垦利区,利津县|菏泽市,曹县,成武县,单县,定陶区,东明县,巨野县,鄄城县,牡丹区,郓城县|济南市,长清区,槐荫区,济阳县,历城区,历下区,平阴县,商河县,市中区,天桥区,章丘市|济宁市,嘉祥县,金乡县,梁山县,曲阜市,任城区,泗水县,微山县,汶上县,兖州区,鱼台县,邹城市|莱芜市,钢城区,莱城区|聊城市,茌平县,东阿县,东昌府区,高唐县,冠县,临清市,莘县,阳谷县|临沂市,费县,河东区,莒南县,兰陵县,兰山区,临沭县,罗庄区,蒙阴县,平邑县,郯城县,沂南县,沂水县|青岛市,城阳区,黄岛区,即墨市,胶州市,莱西市,崂山区,李沧区,平度市,市北区,市南区|日照市,东港区,莒县,岚山区,五莲县|泰安市,岱岳区,东平县,肥城市,宁阳县,泰山区,新泰市|威海市,环翠区,荣成市,乳山市,文登区|潍坊市,安丘市,昌乐县,昌邑市,坊子区,高密市,寒亭区,奎文区,临朐县,青州市,寿光市,潍城区,诸城市|烟台市,长岛县,福山区,海阳市,莱山区,莱阳市,莱州市,龙口市,牟平区,蓬莱市,栖霞市,招远市,芝罘区|枣庄市,山亭区,市中区,台儿庄区,滕州市,薛城区,峄城区|淄博市,博山区,高青县,桓台县,临淄区,沂源县,张店区,周村区,淄川区#山西省$长治市,长治县,长子县,城区,壶关县,郊区,黎城县,潞城市,平顺县,沁县,沁源县,屯留县,武乡县,襄垣县|大同市,城区,大同县,广灵县,浑源县,矿区,灵丘县,南郊区,天镇县,新荣区,阳高县,左云县|晋城市,城区,高平市,陵川县,沁水县,阳城县,泽州县|晋中市,和顺县,介休市,灵石县,平遥县,祁县,寿阳县,太谷县,昔阳县,榆次区,榆社县,左权县|临汾市,安泽县,大宁县,汾西县,浮山县,古县,洪洞县,侯马市,霍州市,吉县,蒲县,曲沃县,隰县,乡宁县,襄汾县,尧都区,翼城县,永和县|吕梁市,方山县,汾阳市,交城县,交口县,岚县,离石区,临县,柳林县,石楼县,文水县,孝义市,兴县,中阳县|朔州市,怀仁县,平鲁区,山阴县,朔城区,应县,右玉县|太原市,古交市,尖草坪区,晋源区,娄烦县,清徐县,万柏林区,小店区,杏花岭区,阳曲县,迎泽区|忻州市,保德县,代县,定襄县,繁峙县,河曲县,静乐县,岢岚县,宁武县,偏关县,神池县,五台县,五寨县,忻府区,原平市|阳泉市,城区,郊区,矿区,平定县,盂县|运城市,河津市,稷山县,绛县,临猗县,平陆县,芮城县,万荣县,闻喜县,夏县,新绛县,盐湖区,永济市,垣曲县#陕西省$安康市,白河县,汉滨区,汉阴县,岚皋县,宁陕县,平利县,石泉县,旬阳县,镇坪县,紫阳县|宝鸡市,陈仓区,凤县,凤翔县,扶风县,金台区,麟游县,陇县,眉县,岐山县,千阳县,太白县,渭滨区|汉中市,城固县,佛坪县,汉台区,留坝县,略阳县,勉县,南郑县,宁强县,西乡县,洋县,镇巴县|商洛市,丹凤县,洛南县,山阳县,商南县,商州区,镇安县,柞水县|铜川市,王益区,耀州区,宜君县,印台区|渭南市,白水县,澄城县,大荔县,富平县,韩城市,合阳县,华阴市,华州区,临渭区,蒲城县,潼关县|西安市,灞桥区,碑林区,长安区,高陵区,户县,蓝田县,莲湖区,临潼区,未央区,新城区,阎良区,雁塔区,周至县|咸阳市,彬县,长武县,淳化县,泾阳县,礼泉县,乾县,秦都区,三原县,渭城区,武功县,兴平市,旬邑县,杨陵区,永寿县|延安市,安塞区,宝塔区,富县,甘泉县,黄陵县,黄龙县,洛川县,吴起县,延长县,延川县,宜川县,志丹县,子长县|榆林市,定边县,府谷县,横山区,佳县,靖边县,米脂县,清涧县,神木县,绥德县,吴堡县,榆阳区,子洲县#上海市$市辖区,宝山区,长宁区,崇明区,奉贤区,虹口区,黄浦区,嘉定区,金山区,静安区,闵行区,浦东新区,普陀区,青浦区,松江区,徐汇区,杨浦区#四川省$阿坝藏族羌族自治州,阿坝县,黑水县,红原县,金川县,九寨沟县,理县,马尔康市,茂县,壤塘县,若尔盖县,松潘县,汶川县,小金县|巴中市,巴州区,恩阳区,南江县,平昌县,通江县|成都市,成华区,崇州市,大邑县,都江堰市,简阳市,金牛区,金堂县,锦江区,龙泉驿区,彭州市,郫县,蒲江县,青白江区,青羊区,邛崃市,双流区,温江区,武侯区,新都区,新津县|达州市,达川区,大竹县,开江县,渠县,通川区,万源市,宣汉县|德阳市,广汉市,旌阳区,罗江县,绵竹市,什邡市,中江县|甘孜藏族自治州,巴塘县,白玉县,丹巴县,道孚县,稻城县,得荣县,德格县,甘孜县,九龙县,康定市,理塘县,泸定县,炉霍县,色达县,石渠县,乡城县,新龙县,雅江县|广安市,广安区,华蓥市,邻水县,前锋区,武胜县,岳池县|广元市,苍溪县,朝天区,剑阁县,利州区,青川县,旺苍县,昭化区|乐山市,峨边彝族自治县,峨眉山市,夹江县,犍为县,金口河区,井研县,马边彝族自治县,沐川县,沙湾区,市中区,五通桥区|凉山彝族自治州,布拖县,德昌县,甘洛县,会东县,会理县,金阳县,雷波县,美姑县,冕宁县,木里藏族自治县,宁南县,普格县,西昌市,喜德县,盐源县,越西县,昭觉县|泸州市,古蔺县,合江县,江阳区,龙马潭区,泸县,纳溪区,叙永县|眉山市,丹棱县,东坡区,洪雅县,彭山区,青神县,仁寿县|绵阳市,安州区,北川羌族自治县,涪城区,江油市,平武县,三台县,盐亭县,游仙区,梓潼县|内江市,东兴区,隆昌县,市中区,威远县,资中县|南充市,高坪区,嘉陵区,阆中市,南部县,蓬安县,顺庆区,西充县,仪陇县,营山县|攀枝花市,东区,米易县,仁和区,西区,盐边县|遂宁市,安居区,船山区,大英县,蓬溪县,射洪县|雅安市,宝兴县,汉源县,芦山县,名山区,石棉县,天全县,荥经县,雨城区|宜宾市,长宁县,翠屏区,高县,珙县,江安县,筠连县,南溪区,屏山县,兴文县,宜宾县|资阳市,安岳县,乐至县,雁江区|自贡市,大安区,富顺县,贡井区,荣县,沿滩区,自流井区#台湾省$#天津市$市辖区,宝坻区,北辰区,滨海新区,东丽区,和平区,河北区,河东区,河西区,红桥区,蓟州区,津南区,静海区,南开区,宁河区,武清区,西青区#西藏自治区$阿里地区,措勤县,噶尔县,改则县,革吉县,普兰县,日土县,札达县|昌都市,八宿县,边坝县,察雅县,丁青县,贡觉县,江达县,卡若区,类乌齐县,洛隆县,芒康县,左贡县|拉萨市,城关区,达孜县,当雄县,堆龙德庆区,林周县,墨竹工卡县,尼木县,曲水县|林芝市,巴宜区,波密县,察隅县,工布江达县,朗县,米林县,墨脱县|那曲地区,安多县,巴青县,班戈县,比如县,嘉黎县,那曲县,尼玛县,聂荣县,申扎县,双湖县,索县|日喀则市,昂仁县,白朗县,定结县,定日县,岗巴县,吉隆县,江孜县,康马县,拉孜县,南木林县,聂拉木县,仁布县,萨嘎县,萨迦县,桑珠孜区,谢通门县,亚东县,仲巴县|山南市,措美县,错那县,贡嘎县,加查县,浪卡子县,隆子县,洛扎县,乃东区,琼结县,曲松县,桑日县,扎囊县#香港特别行政区$#新疆维吾尔自治区$阿克苏地区,阿克苏市,阿瓦提县,拜城县,柯坪县,库车县,沙雅县,温宿县,乌什县,新和县|阿勒泰地区,阿勒泰市,布尔津县,福海县,富蕴县,哈巴河县,吉木乃县,青河县|巴音郭楞蒙古自治州,博湖县,和静县,和硕县,库尔勒市,轮台县,且末县,若羌县,尉犁县,焉耆回族自治县|博尔塔拉蒙古自治州,阿拉山口市,博乐市,精河县,温泉县|昌吉回族自治州,昌吉市,阜康市,呼图壁县,吉木萨尔县,玛纳斯县,木垒哈萨克自治县,奇台县|哈密市,巴里坤哈萨克自治县,伊吾县,伊州区|和田地区,策勒县,和田市,和田县,洛浦县,民丰县,墨玉县,皮山县,于田县|喀什地区,巴楚县,伽师县,喀什市,麦盖提县,莎车县,疏附县,疏勒县,塔什库尔干塔吉克自治县,叶城县,英吉沙县,岳普湖县,泽普县|克拉玛依市,白碱滩区,独山子区,克拉玛依区,乌尔禾区|克孜勒苏柯尔克孜自治州,阿合奇县,阿克陶县,阿图什市,乌恰县|塔城地区,额敏县,和布克赛尔蒙古自治县,沙湾县,塔城市,托里县,乌苏市,裕民县|吐鲁番市,高昌区,鄯善县,托克逊县|乌鲁木齐市,达坂城区,米东区,沙依巴克区,水磨沟区,天山区,头屯河区,乌鲁木齐县,新市区|伊犁哈萨克自治州,察布查尔锡伯自治县,巩留县,霍城县,霍尔果斯市,奎屯市,尼勒克县,特克斯县,新源县,伊宁市,伊宁县,昭苏县|自治区直辖县级行政区划,阿拉尔市,石河子市,铁门关市,图木舒克市,五家渠市#云南省$保山市,昌宁县,龙陵县,隆阳区,施甸县,腾冲市|楚雄彝族自治州,楚雄市,大姚县,禄丰县,牟定县,南华县,双柏县,武定县,姚安县,永仁县,元谋县|大理白族自治州,宾川县,大理市,洱源县,鹤庆县,剑川县,弥渡县,南涧彝族自治县,巍山彝族回族自治县,祥云县,漾濞彝族自治县,永平县,云龙县|德宏傣族景颇族自治州,梁河县,陇川县,芒市,瑞丽市,盈江县|迪庆藏族自治州,德钦县,维西傈僳族自治县,香格里拉市|红河哈尼族彝族自治州,个旧市,河口瑶族自治县,红河县,建水县,金平苗族瑶族傣族自治县,开远市,泸西县,绿春县,蒙自市,弥勒市,屏边苗族自治县,石屏县,元阳县|昆明市,安宁市,呈贡区,东川区,富民县,官渡区,晋宁县,禄劝彝族苗族自治县,盘龙区,石林彝族自治县,嵩明县,五华区,西山区,寻甸回族彝族自治县,宜良县|丽江市,古城区,华坪县,宁蒗彝族自治县,永胜县,玉龙纳西族自治县|临沧市,沧源佤族自治县,凤庆县,耿马傣族佤族自治县,临翔区,双江拉祜族佤族布朗族傣族自治县,永德县,云县,镇康县|怒江傈僳族自治州,福贡县,贡山独龙族怒族自治县,兰坪白族普米族自治县,泸水市|普洱市,江城哈尼族彝族自治县,景东彝族自治县,景谷傣族彝族自治县,澜沧拉祜族自治县,孟连傣族拉祜族佤族自治县,墨江哈尼族自治县,宁洱哈尼族彝族自治县,思茅区,西盟佤族自治县,镇沅彝族哈尼族拉祜族自治县|曲靖市,富源县,会泽县,陆良县,罗平县,马龙县,麒麟区,师宗县,宣威市,沾益区|文山壮族苗族自治州,富宁县,广南县,麻栗坡县,马关县,丘北县,文山市,西畴县,砚山县|西双版纳傣族自治州,景洪市,勐海县,勐腊县|玉溪市,澄江县,峨山彝族自治县,红塔区,华宁县,江川区,通海县,新平彝族傣族自治县,易门县,元江哈尼族彝族傣族自治县|昭通市,大关县,鲁甸县,巧家县,水富县,绥江县,威信县,盐津县,彝良县,永善县,昭阳区,镇雄县#浙江省$杭州市,滨江区,淳安县,富阳区,拱墅区,建德市,江干区,临安市,上城区,桐庐县,西湖区,下城区,萧山区,余杭区|湖州市,安吉县,长兴县,德清县,南浔区,吴兴区|嘉兴市,海宁市,海盐县,嘉善县,南湖区,平湖市,桐乡市,秀洲区|金华市,东阳市,金东区,兰溪市,磐安县,浦江县,武义县,婺城区,义乌市,永康市|丽水市,缙云县,景宁畲族自治县,莲都区,龙泉市,青田县,庆元县,松阳县,遂昌县,云和县|宁波市,北仑区,慈溪市,奉化市,海曙区,江北区,江东区,宁海县,象山县,鄞州区,余姚市,镇海区|衢州市,常山县,江山市,开化县,柯城区,龙游县,衢江区|绍兴市,柯桥区,上虞区,嵊州市,新昌县,越城区,诸暨市|台州市,黄岩区,椒江区,临海市,路桥区,三门县,天台县,温岭市,仙居县,玉环县|温州市,苍南县,洞头区,乐清市,龙湾区,鹿城区,瓯海区,平阳县,瑞安市,泰顺县,文成县,永嘉县|舟山市,岱山县,定海区,普陀区,嵊泗县";
if (ShowT) PCAD = SPT + "$" + SCT + "," + SAT + "#" + PCAD;
PCAArea = [];
PCAP = [];
PCAC = [];
PCAA = [];
PCAN = PCAD.split("#");
for (i = 0; i < PCAN.length; i++) {
    PCAA[i] = [];
    TArea = PCAN[i].split("$")[1].split("|");
    for (j = 0; j < TArea.length; j++) {
        PCAA[i][j] = TArea[j].split(",");
        if (PCAA[i][j].length == 1) PCAA[i][j][1] = SAT;
        TArea[j] = TArea[j].split(",")[0]
    }
    PCAArea[i] = PCAN[i].split("$")[0] + "," + TArea.join(",");
    PCAP[i] = PCAArea[i].split(",")[0];
    PCAC[i] = PCAArea[i].split(',')
}

function PCAS() {
    this.SelP = document.getElementsByName(arguments[0])[0];
    this.SelC = document.getElementsByName(arguments[1])[0];
    this.SelA = document.getElementsByName(arguments[2])[0];
    this.DefP = this.SelA ? arguments[3] : arguments[2];
    this.DefC = this.SelA ? arguments[4] : arguments[3];
    this.DefA = this.SelA ? arguments[5] : arguments[4];
    this.SelP.PCA = this;
    this.SelC.PCA = this;
    this.SelP.onchange = function () {
        PCAS.SetC(this.PCA)
    };
    if (this.SelA) this.SelC.onchange = function () {
        PCAS.SetA(this.PCA)
    };
    PCAS.SetP(this)
};
PCAS.SetP = function (PCA) {
    for (i = 0; i < PCAP.length; i++) {
        PCAPT = PCAPV = PCAP[i];
        if (PCAPT == SPT) PCAPV = "";
        PCA.SelP.options.add(new Option(PCAPT, PCAPV));
        if (PCA.DefP == PCAPV) PCA.SelP[i].selected = true
    }
    PCAS.SetC(PCA)
};
PCAS.SetC = function (PCA) {
    PI = PCA.SelP.selectedIndex;
    PCA.SelC.length = 0;
    PCA.SelC.options.add(new Option('全部', ''));
    for (i = 1; i < PCAC[PI].length; i++) {
        PCACT = PCACV = PCAC[PI][i];
        if (PCACT == SCT) PCACV = "";
        PCA.SelC.options.add(new Option(PCACT, PCACV));
        if (PCA.DefC == PCACV) PCA.SelC[i].selected = true
    }
    if (PCA.SelA) PCAS.SetA(PCA)
};
PCAS.SetA = function (PCA) {
        PI = PCA.SelP.selectedIndex;
        CI = PCA.SelC.selectedIndex;
        PCA.SelA.length = 0;
        PCA.SelA.options.add(new Option('全部', ''));

		if(CI >= 1){
			for (i = 1; i < PCAA[PI][CI - 1].length; i++) {
	            PCAAT = PCAAV = PCAA[PI][CI - 1][i];
	            if (PCAAT == SAT) PCAAV = "";
	            PCA.SelA.options.add(new Option(PCAAT, PCAAV));
	            if (PCA.DefA == PCAAV) PCA.SelA[i].selected = true
	        }
		}
        
    }
    //-->