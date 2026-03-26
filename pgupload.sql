--
-- PostgreSQL database dump
--

\restrict OFr4yP0eupV9JPxloIKfgEhXRvvIqPsIPlHjWi5DtdhYPRZvaI35TfDOGO8pRHV

-- Dumped from database version 18.1 (Postgres.app)
-- Dumped by pg_dump version 18.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: uploads; Type: SCHEMA; Schema: -; Owner: andrewjsykes
--

CREATE SCHEMA uploads;


ALTER SCHEMA uploads OWNER TO andrewjsykes;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: asset; Type: TABLE; Schema: uploads; Owner: andrewjsykes
--

CREATE TABLE uploads.asset (
    id integer NOT NULL,
    filename character varying(100),
    mimetype character varying(100),
    alt character varying(255),
    path character varying(255),
    file character varying(50),
    size numeric(8,2),
    "time" timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE uploads.asset OWNER TO andrewjsykes;

--
-- Name: asset_id_seq; Type: SEQUENCE; Schema: uploads; Owner: andrewjsykes
--

CREATE SEQUENCE uploads.asset_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE uploads.asset_id_seq OWNER TO andrewjsykes;

--
-- Name: asset_id_seq; Type: SEQUENCE OWNED BY; Schema: uploads; Owner: andrewjsykes
--

ALTER SEQUENCE uploads.asset_id_seq OWNED BY uploads.asset.id;


--
-- Name: client; Type: TABLE; Schema: uploads; Owner: andrewjsykes
--

CREATE TABLE uploads.client (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    domain character varying(50),
    tel character varying(20)
);


ALTER TABLE uploads.client OWNER TO andrewjsykes;

--
-- Name: client_id_seq; Type: SEQUENCE; Schema: uploads; Owner: andrewjsykes
--

CREATE SEQUENCE uploads.client_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE uploads.client_id_seq OWNER TO andrewjsykes;

--
-- Name: client_id_seq; Type: SEQUENCE OWNED BY; Schema: uploads; Owner: andrewjsykes
--

ALTER SEQUENCE uploads.client_id_seq OWNED BY uploads.client.id;


--
-- Name: role; Type: TABLE; Schema: uploads; Owner: andrewjsykes
--

CREATE TABLE uploads.role (
    id character varying(255) NOT NULL,
    description character varying(255)
);


ALTER TABLE uploads.role OWNER TO andrewjsykes;

--
-- Name: upload; Type: TABLE; Schema: uploads; Owner: andrewjsykes
--

CREATE TABLE uploads.upload (
    id integer NOT NULL,
    filename character varying(100),
    mimetype character varying(100),
    description character varying(255),
    filepath character varying(255),
    file character varying(50),
    size numeric(8,2),
    userid integer NOT NULL,
    "time" timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE uploads.upload OWNER TO andrewjsykes;

--
-- Name: upload_id_seq; Type: SEQUENCE; Schema: uploads; Owner: andrewjsykes
--

CREATE SEQUENCE uploads.upload_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE uploads.upload_id_seq OWNER TO andrewjsykes;

--
-- Name: upload_id_seq; Type: SEQUENCE OWNED BY; Schema: uploads; Owner: andrewjsykes
--

ALTER SEQUENCE uploads.upload_id_seq OWNED BY uploads.upload.id;


--
-- Name: userrole; Type: TABLE; Schema: uploads; Owner: andrewjsykes
--

CREATE TABLE uploads.userrole (
    userid integer NOT NULL,
    roleid character varying(255) DEFAULT 'Client'::character varying NOT NULL
);


ALTER TABLE uploads.userrole OWNER TO andrewjsykes;

--
-- Name: usr; Type: TABLE; Schema: uploads; Owner: andrewjsykes
--

CREATE TABLE uploads.usr (
    id integer NOT NULL,
    name character varying(255),
    email character varying(255),
    password character(32),
    client_id integer
);


ALTER TABLE uploads.usr OWNER TO andrewjsykes;

--
-- Name: usr_id_seq; Type: SEQUENCE; Schema: uploads; Owner: andrewjsykes
--

CREATE SEQUENCE uploads.usr_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE uploads.usr_id_seq OWNER TO andrewjsykes;

--
-- Name: usr_id_seq; Type: SEQUENCE OWNED BY; Schema: uploads; Owner: andrewjsykes
--

ALTER SEQUENCE uploads.usr_id_seq OWNED BY uploads.usr.id;


--
-- Name: asset id; Type: DEFAULT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.asset ALTER COLUMN id SET DEFAULT nextval('uploads.asset_id_seq'::regclass);


--
-- Name: client id; Type: DEFAULT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.client ALTER COLUMN id SET DEFAULT nextval('uploads.client_id_seq'::regclass);


--
-- Name: upload id; Type: DEFAULT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.upload ALTER COLUMN id SET DEFAULT nextval('uploads.upload_id_seq'::regclass);


--
-- Name: usr id; Type: DEFAULT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.usr ALTER COLUMN id SET DEFAULT nextval('uploads.usr_id_seq'::regclass);


--
-- Data for Name: asset; Type: TABLE DATA; Schema: uploads; Owner: andrewjsykes
--

COPY uploads.asset (id, filename, mimetype, alt, path, file, size, "time") FROM stdin;
\.


--
-- Data for Name: client; Type: TABLE DATA; Schema: uploads; Owner: andrewjsykes
--

COPY uploads.client (id, name, domain, tel) FROM stdin;
1	Tribal Education	tribalgroup.com	01904 550130
2	Bluestorm Design	bluestormdesign.co.uk	01482 649343
3	The Skills Network	theskillsnetwork.co.uk	\N
4	Systematic	systematicprint.com	\N
5	Larchfield	larchfieldassociates.co.uk	\N
6	The Rory Peck Trust	rorypecktrust.org	\N
9	40twenty	40twenty.co.uk	01904 479045
10	Pavilion	paviliongroup.co.uk	\N
11	Webmart	webmartuk.com	\N
12	The Small Agency	thesmallagency.co.uk	\N
13	Wish	wish-agency.co.uk	\N
14	Shepherd Building Group	shepherd-group.com	\N
15	Hague Print	hagueprint.com	\N
16	Scarlett Abbott	scarlettabbott.co.uk	\N
17	Bray Design	braydesign.co.uk	01625471569
18	The Whole Cabodle	thewholecaboodle.com	
20	Fulford Golf Club	fulfordgolfclub.co.uk	01904 413579
21	ES Print Solutions	esprintsolutions.com	\N
22	The Design Bank	thedesignbank.co.uk	\N
23	BN Thermic	bnthermic.co.uk	01293 547 361
25	Red Publications	redpublications.com	\N
26	Impression Design & Print	impressiondp.co.uk	\N
27	Craig Westwood Design Services	craigwestwood.co.uk	\N
28	Number Fun	numberfun.co.uk	\N
29	The Whisky Lounge	thewhiskylounge.com	\N
31	Gavin Ward Design Associates	gwda.co.uk	\N
32	Full Circle Creative	fullcirclecreative.com	\N
33	Lee Goater	leegoater.com	\N
34	Colour Options Ltd	colouroptions.co.uk	01904 â€¯608158
36	Saltmer Design	saltmerdesign.co.uk	\N
38	Bluestone Design	bluestonedesign.co.uk	
39	Freckle	freckleonline.com	\N
40	Summit Media	summitmedia.com	\N
43	Simon Brown	sb@copgrove.demon.co.uk	\N
46	Emphasis Design	emphasisdesign.co.uk	\N
48	Rubber Band	rubberbandisthe.biz	\N
50	Fusion	fusioned.co.uk	
51	Martin House Hospice	martinhouse.org.uk	01937 845045
52	Pocklington Arts Centre	pocklingtonartscentre.co.uk	
53	Quantum Creative	quantumcreative.co.uk	01482 572343
54	Blue Ginger	blueginger.co.uk	01484 506506
55	Transmit Creative	transmitcreative.co.uk	0845 465 6666 
56	Next Level	nextlevel.co.uk	01274 855860
57	Askham Bryan College	askham-bryan.ac.uk	01904 772277
58	Lazenby Brown	lazenbybrown.com	01904 622 999
61	Visit York	visityork.org	01904 550099
63	National Science Learning Centre	slcs.ac.uk	01904 328300
65	Integral Design	integraldesignmedia.co.uk	
66	Footprints Media	footprintsmedia.co.uk	
67	York St John University	yorksj.ac.uk	
68	Design By Mint	designbymint.com	07790 595 868
69	Martin Design Associates	martindesignassociates.com	01904 48846
70	There Ltd	getting-there.co.uk	
71	Space Creative	spacecreative.co.uk	01757 618222
72	Frontier Communications	frontier-communications.com	07703063848
74	Jump	jumpconsultancy.co.uk	07881 596169
97	Granthams	granthamsfinefood.com	01625 583286
99	Thunderbirds	tbsrgo.co.uk	
100	Andy Welland	andywelland.com	
101	Maximise	maxi-mise.com	01904 215145
102	The Specialists	thespecialists.org.uk	01609 711000
103	HA Creative	ha-creative.com	024 7659 0416
105	Cookie Graphic Design	cookiegraphicdesign.co.uk	07796 821063
106	Blondie	blonde@blondie.com	
107	Eshre	eshre.eu	
108	Sowden & Sowden	sowden-sowden.co.uk	01482 649311
109	Scamp Creative	scampcreative.co.uk	0113 834 6555
\.


--
-- Data for Name: role; Type: TABLE DATA; Schema: uploads; Owner: andrewjsykes
--

COPY uploads.role (id, description) FROM stdin;
Admin	Add, remove, and edit ALL files
Browser	Allowed to view uploaded files only
Client	Add, remove, and edit USER files
Client Admin	Add,remove,edit and assign roles to USER files
Manager	Upload as Client
\.


--
-- Data for Name: upload; Type: TABLE DATA; Schema: uploads; Owner: andrewjsykes
--

COPY uploads.upload (id, filename, mimetype, description, filepath, file, size, userid, "time") FROM stdin;
2023	golden.jpg	image/jpeg		../../filestore/	1773222779.jpg	1118.79	3	2026-03-11 09:52:59+00
2024	Normans_poster.jpg	image/jpeg		../../filestore/	1773222787.jpg	832.28	3	2026-03-11 09:53:07+00
2025	golden.jpg	image/jpeg		../../filestore/	1773222817.jpg	1118.79	107	2026-03-11 09:53:37+00
2026	Normans_poster.jpg	image/jpeg		../../filestore/	1773222828.jpg	832.28	107	2026-03-11 09:53:48+00
2027	Untitled.rtf	text/rtf		../../filestore/	1773304991.rtf	0.53	1	2026-03-12 08:43:11+00
2028	fab1.jpeg	image/jpeg		../../filestore/	1773341044.jpeg	3.66	1	2026-03-12 18:44:04+00
2030	mum90.pdf	application/pdf	Updated Poster 2	../../filestore/	1773409135.pdf	21780.81	119	2026-03-13 13:38:55+00
\.


--
-- Data for Name: userrole; Type: TABLE DATA; Schema: uploads; Owner: andrewjsykes
--

COPY uploads.userrole (userid, roleid) FROM stdin;
1	Admin
2	Admin
3	Admin
4	Client Admin
5	Client Admin
6	Client Admin
7	Client Admin
8	Client Admin
9	Admin
10	Client Admin
11	Client Admin
12	Client Admin
13	Client Admin
14	Client Admin
15	Client Admin
16	Client Admin
17	Client Admin
18	Client Admin
19	Client
20	Client Admin
21	Client Admin
22	Client Admin
23	Client Admin
24	Client
25	Client Admin
26	Client
27	Client Admin
28	Client Admin
29	Client
31	Client Admin
32	Client Admin
33	Client
34	Client Admin
35	Client
36	Client Admin
37	Client Admin
38	Client
39	Client Admin
40	Client
41	Client Admin
42	Client
43	Client
44	Client
45	Client Admin
46	Client Admin
47	Client Admin
49	Client Admin
50	Client
51	Client Admin
52	Client Admin
53	Client
54	Client Admin
55	Client Admin
56	Client Admin
57	Client Admin
58	Client Admin
59	Client
60	Client Admin
61	Client Admin
62	Client Admin
63	Client
64	Client Admin
65	Client
66	Client Admin
67	Client Admin
68	Client Admin
70	Client
71	Client Admin
72	Client
73	Client Admin
74	Client
76	Client
77	Client Admin
78	Client
79	Client Admin
80	Client Admin
81	Client
82	Client Admin
83	Client Admin
84	Client
85	Client Admin
86	Client
87	Client
88	Client Admin
89	Client Admin
90	Client
91	Client Admin
92	Client Admin
93	Client
94	Client Admin
95	Client Admin
97	Client Admin
98	Client Admin
99	Client Admin
100	Client Admin
101	Client
102	Client Admin
103	Client
104	Client Admin
105	Client
106	Client Admin
107	Client Admin
108	Client
109	Client Admin
110	Client Admin
111	Manager
112	Client Admin
113	Manager
114	Browser
115	Client Admin
116	Client Admin
117	Manager
118	Manager
119	Client
\.


--
-- Data for Name: usr; Type: TABLE DATA; Schema: uploads; Owner: andrewjsykes
--

COPY uploads.usr (id, name, email, password, client_id) FROM stdin;
1	Andrew Sykes	andrewsykes@btinternet.com	bd9f3b6f2fd132df9a6e86cb431a3d2b	\N
2	Mike Hawe	mike.hawe@northwolds.co.uk	db084e7fddbfeeddefcbf8aaa4f05516	\N
3	Chris Sykes	chris.sykes@northwolds.co.uk	db084e7fddbfeeddefcbf8aaa4f05516	\N
4	Nick Saltmer	nick@saltmerdesign.co.uk	e546a469eb71ef19c16956bb4e6deb35	36
5	Gavin Ward	gavin@gwda.co.uk	e546a469eb71ef19c16956bb4e6deb35	31
6	Jonathan Abbott	jonathan@scarlettabbott.co.uk	c948fe27c05f17d37fbaed47f8d3a9e7	16
7	Philip Beadle	philip@bluestonedesign.co.uk	e546a469eb71ef19c16956bb4e6deb35	38
8	Paul Thornton	production@larchfieldassociates.co.uk	e546a469eb71ef19c16956bb4e6deb35	5
9	North Wolds	files@northwolds.co.uk	546ebf587a34e8ba5f4713497a85aa79	\N
10	Chris Sharp	chrissharp@me.com	e546a469eb71ef19c16956bb4e6deb35	\N
11	Eddie Ludlow	eddie@thewhiskylounge.com	9a71776c36add91b88d63cf779e6cfce	29
12	Matty Magiera	matty.magiera@webmartuk.com	add7fb34940bd5503020e43ffb1ea193	11
13	Andrew Brookes	andrewbrookes@paviliongroup.co.uk	e546a469eb71ef19c16956bb4e6deb35	10
14	Craig Westwood	craig@craigwestwood.co.uk	e546a469eb71ef19c16956bb4e6deb35	27
15	Sharon Carlton	sharon@systematicprint.com	e546a469eb71ef19c16956bb4e6deb35	4
16	Rob Snaith	rob@footprintsmedia.co.uk	e546a469eb71ef19c16956bb4e6deb35	66
17	Jenny Laycock	jenny.laycock@tribalgroup.com	8161578fe281f785a702c4d6e3219275	1
18	Stephen Collier	stephen@40twenty.co.uk	e546a469eb71ef19c16956bb4e6deb35	9
19	Jonathan Norman	jonathan@systematicprint.com	e546a469eb71ef19c16956bb4e6deb35	4
20	Design Bank	info@thedesignbank.co.uk	e546a469eb71ef19c16956bb4e6deb35	22
21	Eliot Grant	eliot.grant@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
22	Lee Goater	me@leegoater.com	e546a469eb71ef19c16956bb4e6deb35	33
23	Caroline Ayres	carolinedayres@btinternet.com	d8dddfe568ce70d4201e27aa444b14e2	\N
24	Helen Austin	helen@systematicprint.com	e546a469eb71ef19c16956bb4e6deb35	4
25	Ellie Shopova	Ellie.Shopova@summitmedia.com	e546a469eb71ef19c16956bb4e6deb35	40
26	Mark Taylor	mark.taylor@scarlettabbott.co.uk	e546a469eb71ef19c16956bb4e6deb35	16
27	Eamonn Croft	eamonn@esprintsolutions.com	e546a469eb71ef19c16956bb4e6deb35	21
28	Dave Godfrey	dave@numberfun.co.uk	e546a469eb71ef19c16956bb4e6deb35	28
29	Richard Boon	richard.boon@webmartuk.com	e546a469eb71ef19c16956bb4e6deb35	11
31	Molly Clarke	molly@rorypecktrust.org	e546a469eb71ef19c16956bb4e6deb35	6
32	John Basker	john@bluestormdesign.co.uk	e546a469eb71ef19c16956bb4e6deb35	2
33	Chloe Rendall	Chloe.Rendall@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
34	Diana K Sissons	sissons-gallery@ic24.net	e546a469eb71ef19c16956bb4e6deb35	\N
35	Kay Jackson	kay@rorypecktrust.org	e546a469eb71ef19c16956bb4e6deb35	6
36	Paul Moorhouse	paul@thewholecaboodle.com	e546a469eb71ef19c16956bb4e6deb35	18
37	Anne Harlow	anne.harlow@shepherd-group.com	c9da39fff967b101fc33407d9bf9619a	14
38	Joe Holliday	joe.holliday@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
39	Andy Douse	andy.douse@redpublications.com	e546a469eb71ef19c16956bb4e6deb35	25
40	Lorna Cole	lorna@systematicprint.com	e546a469eb71ef19c16956bb4e6deb35	4
41	Hannah Gregory	hannah@thesmallagency.co.uk	e546a469eb71ef19c16956bb4e6deb35	12
42	Tina Carr	tina@rorypecktrust.org	e546a469eb71ef19c16956bb4e6deb35	6
43	Simone Williams	simone@systematicprint.com	e546a469eb71ef19c16956bb4e6deb35	4
44	Mike Broster	mike@systematicprint.com	e546a469eb71ef19c16956bb4e6deb35	4
45	Maggie Leaning	maggie@fullcirclecreative.com	e546a469eb71ef19c16956bb4e6deb35	32
46	Matt Taylor	matt@colouroptions.co.uk	e546a469eb71ef19c16956bb4e6deb35	34
47	Charlie Hartley	charlie@impressiondp.co.uk	e546a469eb71ef19c16956bb4e6deb35	26
49	Joanne Cusick	Joanne.Cusick@hagueprint.com	e546a469eb71ef19c16956bb4e6deb35	15
50	Michael Domney	mike@bluestormdesign.co.uk	e546a469eb71ef19c16956bb4e6deb35	2
51	Simon Brown	sb@copgrove.demon.co.uk	e546a469eb71ef19c16956bb4e6deb35	43
52	Richard Evans	richard.evans@bnthermic.co.uk	e546a469eb71ef19c16956bb4e6deb35	23
53	Pagon Hemmingway	Pagon.Hemingway@tribalgroup.com	e546a469eb71ef19c16956bb4e6deb35	1
54	Ross Morris	ross@freckleonline.com	e546a469eb71ef19c16956bb4e6deb35	39
55	Fiona Kelsall	fiona@wish-agency.co.uk	e546a469eb71ef19c16956bb4e6deb35	13
56	Michelle Thompson	michellethompson@braydesign.co.uk	e546a469eb71ef19c16956bb4e6deb35	17
57	Ross	ross@emphasisdesign.co.uk	e546a469eb71ef19c16956bb4e6deb35	46
58	Martin Cockerham	martin@nextlevel.co.uk	e546a469eb71ef19c16956bb4e6deb35	56
59	Wendy Griffiths	Wendy.Griffiths@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
60	Kerry Wicklow	kerry@blueginger.co.uk	e546a469eb71ef19c16956bb4e6deb35	54
61	Chris Hammond	chrishammond@ha-creative.com	e546a469eb71ef19c16956bb4e6deb35	103
62	Martyn Lee	martyn@bluemantis.com	e546a469eb71ef19c16956bb4e6deb35	\N
63	Rhiannon Moxey	rhiannon.moxey@tribalgroup.com	e546a469eb71ef19c16956bb4e6deb35	1
64	Mat Lazenby	mat@lazenbybrown.com	e546a469eb71ef19c16956bb4e6deb35	58
65	Catherine Easterbrook	Catherine.Easterbrook@tribalgroup.com	e546a469eb71ef19c16956bb4e6deb35	1
66	Peter Syson	peter@rubberbandisthe.biz	e546a469eb71ef19c16956bb4e6deb35	48
67	Martin Denton	martin.denton@sbs.co.uk	e546a469eb71ef19c16956bb4e6deb35	\N
68	Sam Ewan	sam@quantumcreative.co.uk	e546a469eb71ef19c16956bb4e6deb35	53
70	Paul	paul.l@nextlevel.co.uk	e546a469eb71ef19c16956bb4e6deb35	56
71	Andy Welland	andy@andywelland.com	e546a469eb71ef19c16956bb4e6deb35	100
72	Abigail Clay	Abigail.Clay@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
73	Matthew Riley	info@fusioned.co.uk	e546a469eb71ef19c16956bb4e6deb35	50
74	Ben Pollard	ben@wish-agency.co.uk	e546a469eb71ef19c16956bb4e6deb35	13
76	Joshua McRandal	scarlettabbott.co.uk	e546a469eb71ef19c16956bb4e6deb35	16
77	James Duffy	james.duffy@pocklingtonartscentre.co.uk	e546a469eb71ef19c16956bb4e6deb35	52
78	Antony Gaukroger	antony.gaukroger@shepherd-group.com	e546a469eb71ef19c16956bb4e6deb35	14
79	Jon Dale	jdale@transmitcreative.co.uk	e546a469eb71ef19c16956bb4e6deb35	55
80	Andy Hudson	info@maxi-mise.com	e546a469eb71ef19c16956bb4e6deb35	101
81	Lizzie Brough	Lizzie.Brough@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
82	Sue Frumin	sf@visityork.org	e546a469eb71ef19c16956bb4e6deb35	61
83	Mark Smith	mark@yorkgraphicdesigners.co.uk	f1d2ff86f3e471e373f9b540d2ba9483	\N
84	Chris Tye	chris.tye@shepherd-group.com	feaaacb8d7ef988859656b333606d038	14
85	Ian Mair	ianmairsterling2@sterling2.karoo.co.uk	e546a469eb71ef19c16956bb4e6deb35	\N
86	Kelly Mcallister	Kelly.Mcallister@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
87	Brenda Clark	bren@slaterclark.co.uk	e546a469eb71ef19c16956bb4e6deb35	\N
88	Mary Perham	m.perham@slcs.ac.uk	e546a469eb71ef19c16956bb4e6deb35	63
89	Craig Cameron	craig.cameron7@ntlworld.com	e546a469eb71ef19c16956bb4e6deb35	\N
90	Phil Midgley	phil@blueginger.co.uk	e546a469eb71ef19c16956bb4e6deb35	54
91	David Emery	david@getting-there.co.uk	e546a469eb71ef19c16956bb4e6deb35	\N
92	Timothy Walker	tim@integraldesignmedia.co.uk	e546a469eb71ef19c16956bb4e6deb35	65
93	John Godwin	john.godwin@summitmedia.com	e546a469eb71ef19c16956bb4e6deb35	40
94	Judith Coates	j.coates@yorksj.ac.uk	e546a469eb71ef19c16956bb4e6deb35	67
95	Andrew Hastwell	andrewhastwell@designbymint.com	e546a469eb71ef19c16956bb4e6deb35	68
97	Annie Backhouse Cook	annie@thespecialists.org.uk	e546a469eb71ef19c16956bb4e6deb35	102
98	Mike Smethurst	mike@spacecreative.co.uk	e546a469eb71ef19c16956bb4e6deb35	71
99	Claire Murray	claire@frontier-communications.com	e546a469eb71ef19c16956bb4e6deb35	72
100	Nigel Howlett	studio3@sowden-sowden.co.uk	e546a469eb71ef19c16956bb4e6deb35	108
101	Laura Echarri	laura@theskillsnetwork.co.uk	e546a469eb71ef19c16956bb4e6deb35	3
102	Matthew Wood	matt@scampcreative.co.uk	e546a469eb71ef19c16956bb4e6deb35	109
103	Emma Laverty	elaverty@hagueprint.com	e546a469eb71ef19c16956bb4e6deb35	15
104	Lisa Cook	info@cookiegraphicdesign.co.uk	e546a469eb71ef19c16956bb4e6deb35	105
105	Fiona Mitchell	fiona.mitchell@wish-agency.co.uk	e546a469eb71ef19c16956bb4e6deb35	13
106	Karen Maris	karen@eshre.eu	d8af26dd8fc23cb9d15b291d3d91795f	107
107	Jacquey Parker	jacquey@jumpconsultancy.co.uk	97c566111f3e30ba09876910d6dd0392	74
108	Kurt Calder	kurt.calder@shepherd-group.com	e546a469eb71ef19c16956bb4e6deb35	14
109	David Brewster	brewsterdavid4@gmail.com	1350fa839a1cbadf0b43157039cf349a	\N
110	Mike Grantham	info@granthamsfinefood.com	e1a8a4e729ddf40849ece72a1e4cf1bd	97
111	Amanda Ludlow	amanda@thewhiskylounge.com	\N	29
112	Jeff Tracy	jeff.tracy@tbsrgo.co.uk	b10497c54b7aaf336229a1431f501369	99
113	Scott Tracy	scott.tracy@tbsrgo.co.uk	b10497c54b7aaf336229a1431f501369	99
114	Alan Tracy	alan.tracy@tbsrgo.co.uk	b10497c54b7aaf336229a1431f501369	99
115	Aloysius Parker	parker@fulfordgolfclub.co.uk	3eaeadf3d15478332368096b31fa0c44	20
116	Billy Yonj	billy@askham-bryan.ac.uk	e546a469eb71ef19c16956bb4e6deb35	57
117	Virgil Tracy	virgil@tbsrgo.co.uk	e546a469eb71ef19c16956bb4e6deb35	99
118	Gordon Tracy	gordon.tracy@tbsrgo.co.uk	e546a469eb71ef19c16956bb4e6deb35	99
119	Kim Kerby	sales@ppsua.com	e546a469eb71ef19c16956bb4e6deb35	\N
\.


--
-- Name: asset_id_seq; Type: SEQUENCE SET; Schema: uploads; Owner: andrewjsykes
--

SELECT pg_catalog.setval('uploads.asset_id_seq', 1, true);


--
-- Name: client_id_seq; Type: SEQUENCE SET; Schema: uploads; Owner: andrewjsykes
--

SELECT pg_catalog.setval('uploads.client_id_seq', 109, true);


--
-- Name: upload_id_seq; Type: SEQUENCE SET; Schema: uploads; Owner: andrewjsykes
--

SELECT pg_catalog.setval('uploads.upload_id_seq', 2030, true);


--
-- Name: usr_id_seq; Type: SEQUENCE SET; Schema: uploads; Owner: andrewjsykes
--

SELECT pg_catalog.setval('uploads.usr_id_seq', 119, true);


--
-- Name: asset idx_16545_primary; Type: CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.asset
    ADD CONSTRAINT idx_16545_primary PRIMARY KEY (id);


--
-- Name: client idx_16555_primary; Type: CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.client
    ADD CONSTRAINT idx_16555_primary PRIMARY KEY (id);


--
-- Name: role idx_16561_primary; Type: CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.role
    ADD CONSTRAINT idx_16561_primary PRIMARY KEY (id);


--
-- Name: upload idx_16568_primary; Type: CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.upload
    ADD CONSTRAINT idx_16568_primary PRIMARY KEY (id);


--
-- Name: userrole idx_16578_primary; Type: CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.userrole
    ADD CONSTRAINT idx_16578_primary PRIMARY KEY (userid, roleid);


--
-- Name: usr idx_16585_primary; Type: CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.usr
    ADD CONSTRAINT idx_16585_primary PRIMARY KEY (id);


--
-- Name: idx_16555_email_domain; Type: INDEX; Schema: uploads; Owner: andrewjsykes
--

CREATE UNIQUE INDEX idx_16555_email_domain ON uploads.client USING btree (domain);


--
-- Name: idx_16555_name_uniq; Type: INDEX; Schema: uploads; Owner: andrewjsykes
--

CREATE UNIQUE INDEX idx_16555_name_uniq ON uploads.client USING btree (name);


--
-- Name: idx_16568_user_ix; Type: INDEX; Schema: uploads; Owner: andrewjsykes
--

CREATE INDEX idx_16568_user_ix ON uploads.upload USING btree (userid);


--
-- Name: idx_16578_role_ix; Type: INDEX; Schema: uploads; Owner: andrewjsykes
--

CREATE INDEX idx_16578_role_ix ON uploads.userrole USING btree (roleid);


--
-- Name: idx_16578_user_ix; Type: INDEX; Schema: uploads; Owner: andrewjsykes
--

CREATE INDEX idx_16578_user_ix ON uploads.userrole USING btree (userid);


--
-- Name: idx_16585_client_ix; Type: INDEX; Schema: uploads; Owner: andrewjsykes
--

CREATE INDEX idx_16585_client_ix ON uploads.usr USING btree (client_id);


--
-- Name: idx_16585_email_name; Type: INDEX; Schema: uploads; Owner: andrewjsykes
--

CREATE UNIQUE INDEX idx_16585_email_name ON uploads.usr USING btree (email);


--
-- Name: usr client_fk; Type: FK CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.usr
    ADD CONSTRAINT client_fk FOREIGN KEY (client_id) REFERENCES uploads.client(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: userrole role_fk; Type: FK CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.userrole
    ADD CONSTRAINT role_fk FOREIGN KEY (roleid) REFERENCES uploads.role(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: upload user_fk; Type: FK CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.upload
    ADD CONSTRAINT user_fk FOREIGN KEY (userid) REFERENCES uploads.usr(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: userrole user_role_fk; Type: FK CONSTRAINT; Schema: uploads; Owner: andrewjsykes
--

ALTER TABLE ONLY uploads.userrole
    ADD CONSTRAINT user_role_fk FOREIGN KEY (userid) REFERENCES uploads.usr(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict OFr4yP0eupV9JPxloIKfgEhXRvvIqPsIPlHjWi5DtdhYPRZvaI35TfDOGO8pRHV

