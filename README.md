
### Prasības
| Nr. | Lietotāju stāsts<lietotājs> vēlas <Sasniegt mērķii>, jo <ieguvums>                                                                             | Prioritāte |
| :-- | :--------------------------------------------------------------------------------------------------------------------------------------------: | :---------: |
| 1.  | Lietotājs vēlas iegūt ēdienkarti, jo grib ietaupīt naudu.                                                                                      | 1          |
| 2.  | Lietotājs vēlas vēlas ar vitamīniem bagātāku ēdienkarti, jo vēlas dzīvot labāk un pilnvērtīgāk.                                                | 8          |
| 3.  | Lietotājs vēlas izvēlēties kādus produktus iekļaut, jo grib personalizēt ēdienkarti.                                                           | 3          |
| 4.  | Lietotājs vēlas, lai sistēma piedāvā orģinālu ēdienkarti, jo nevar izdomāt ko grib ēst.                                                        | 6          |
| 5.  | Lietotājs vēlas ar limitētiem produktiem izveidot ēdienkarti, jo vēlas redzēt iespējas, ko ar šiem produktiem iespējams pagatavot.             | 9          |
| 6.  | Lietotājs vēlas ātri pagatavojamas receptes, jo vēlas ietaupīt laiku.                                                                          | 2          |
| 7.  | Lietotājs vēlas lielu un daudzveidīgu produktu izvēli, jo vēlas, lai ēdienu plānošana būtu pilnvērtīga un elastīga.                            | 4          |
| 8.  | Lietotājs vēlas redzēt ēdienkartes vēsturi, jo grib zināt ko iepriekš ir izveidojis vai ēdis.                                                  | 7          |  
| 9.  | Lietotājs vēlas pielāgot ēdienkarti saviem fitnesa mērķiem, jo nepieciešams audzēt muskuļu masu, zaudētu svaru, turēt augstu enerģijas līmeni. | 5          |
| 10. | Lietotājs vēlas lai sastāvdaļu daudzums tiek pielāgots atkarībā cilvēku skaitam, jo iespējams nepieciešams gatavot ģimenei, svinībām utt.      | 11         |
| 11. | Lietotājs vēlas redzēt produktiem bildes, jo vēlas ātri saprast, kas ir ēdienkartē iekļauts, vai kādus produktus izvēlēties.                   | 10         |

## Projekta apraksts

Šī PHP tīmekļa lietotne palīdz lietotājiem plānot uzturā sabalansētas, personalizētas ēdienkartes, balstoties uz lietotāja mērķiem, pieejamajiem produktiem un uzturvielu datiem.

## Funkcionalitāte

- Produktu CSV augšupielāde un datu bāzes sinhronizācija
- Reģistrācija un pieteikšanās
- Lietotāja profils un vēsture
- Uzturvērtību analīze un meklēšana
- Ēdienkartes ģenerēšana pēc izvēlētiem kritērijiem
- Web skrāpēšana ar `WEB_scrape.py` un `WEB_scrape_CenaKg.py`

## Tehnoloģijas

- **PHP** – servera puses loģika
- **CSV** – datu avoti ar uzturvērtībām
- **MySQL** - datubāze
- **Python** – datu apstrādei un skrāpēšanai
- **HTML/CSS/JavaScript** – lietotāja saskarnei

## Uzstādīšana

1. Augšupielādē projekta saturu tīmekļa serverī (Apache, Nginx u.c.).
2. Nodrošini, ka PHP ir aktivizēts.
3. Ja tiek izmantota MySQL datubāze – izveido to un konfigurē `CSVtoDB.php`.
4. Izpildi skriptus, lai importētu CSV failus datubāzē.
5. Pārlūkprogrammā atver `index.php`.

## Svarīgākās datnes

- `index.php` – sākumlapa
- `Login.php`, `SignUp.php` – autentifikācija
- `profile.php` – lietotāja profils
- `history.php` – ēdienkaršu vēsture
- `CSVtoDB.php` – CSV datu ielāde datubāzē
- `WEB_scrape.py`, `WEB_scrape_CenaKg.py` – dati no interneta
- CSV faili (`produkti_*.csv`) – uzturvērtību dati dažādiem produktu veidiem

### Konceptu modelis
![image](https://github.com/user-attachments/assets/5c85a403-5ca4-4f2e-b1e0-723943bf81dc)

![image](https://github.com/user-attachments/assets/86018120-bcba-4569-aea0-6f16c005facb)

<!---![image](https://github.com/user-attachments/assets/06132cd9-2d90-49e7-bb20-9f94c6bc23b8)-->
Lietotājs izvēlās produktus no Ēdienu produkti, kuri tiek ņemti caur web scraping un pievino algoritmam ko obligāti vajadzētu aprēķinos kā minimizēt cenu un izvēlēties vai algoritms pievieno vēl klāt, tad veidojās saite starp ēdiens_produkti kur savieno vairākus Ēdienu produktus ar Ēdienu, Ēdieni satur visu vēsturi par izveidotiem ēdieniem izmantotiem produktiem un vitamīniem.
# majaslapas index izskats
![image](https://github.com/user-attachments/assets/6be5e888-542f-4c38-ac0a-cd1f7421b667)


