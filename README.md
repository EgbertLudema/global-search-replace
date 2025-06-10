# Global search & replace
Met deze plugin kun je zoeken door alle databases van je Wordpress website waarna je kunt aangeven waarmee je dit woordt eventueel mee wil vervangen. 
Na de zoekopdracht krijg je eerst een preview van alle resultaten te zien(hierbij zijn alle revisies uitgesloten) waarna je kunt aanvinken welke rijen je wilt vervangen, of alle resultaten kunt vervangen.
Deze plugin is in te schakelen voor klanten, hierbij heeft een klant wel beperkt toegang tot database tabellen.

## Voor klanten
Standaard staat de plugin uit voor klanten. Deze zou je aan kunnen zetten d.m.v. de Members plugin:
1. Navigeer naar "**Members->Rollen**"
2. Bewerk de klanten rol
3. Onder het tabje "**Aangepast**" kun je de setting "**gsr_acces**" toestaan of juist niet.

Op deze manier kun je je klanten ook toegang geven tot de app. 

> "gsr" is een afkorting voor global search & replace

Klanten hebben overigens alleen toegang tot 2 content tabellen in de database en kunnen de Regex optie niet gebruiken. 
De content tabellen zijn:

1. **wp_posts** 
2. **wp_postmeta**

Dit kun je aanpassen door dit in de code aan te passen. Ik raad dit niet aan!

> ## LETOP!
> Als je niet weet wat je doet, is het mogelijk je Wordpress website te slopen. 
> Maak om deze reden vooraf **ALTIJD** een **BACK-UP** van je database zodat je deze, indien je iets sloopt, terug kunt zetten!!!

## Functies van de plugin:

- De mogelijkheid te zoeken in specifieke database tabellen
- De optie om met Regex te zoeken, bijvoorbeeld op patronen, teksten beginnend met ..., etc.
- Sluit revisies uit van zoekresultaten en database updates
- Weergeeft links naar posts bij zoekresultaten
- Sticky headers per rij voor gebruiksvriendelijkheid
- De optie om rijen uit de zoekresultaten te selecteren en alleen deze te vervangen
- De optie om alle zoekresultaten in 1x te vervangen

### Voor klanten:

- Optie om de plugin in of uit te schakelen voor klanten
- Beperkt toegang tot database tabellen(alleen content tabellen)

---

> Deze plugin is gemaakt door [Egbert Ludema](https://www.egbertludema.com/) als 1 van de stage opdrachten tijdens de stage periode van Feb 2025 tot Jun 2025 bij [Webwijs](https://www.webwijs.nu/).