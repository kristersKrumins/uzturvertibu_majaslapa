from bs4 import BeautifulSoup
import requests
import re
import sys
import csv
import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException

# Set UTF-8 encoding to support special characters in the console
sys.stdout.reconfigure(encoding='utf-8')

# Base URL and request headers
bāzes_url = "https://www.rimi.lv"
GALVENES = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'
}

# Set up Selenium WebDriver (adjust path to chromedriver.exe as needed)
pakalpojums = Service('C:/Users/krima/Downloads/chromedriver-win64/chromedriver.exe')
pārlūks = webdriver.Chrome(service=pakalpojums)

# Maximum number of nutritional values to capture
MAX_UZTURVĒRTĪBU_SK = 9  # Includes space for Salt

# Open CSV file for writing with UTF-8 BOM encoding
with open('produkti_saldumi.csv', mode='w', newline='', encoding='utf-8-sig') as fails:
    rakstītājs = csv.writer(fails)
    # Write CSV header with column names in the specified order
    galvene = [
        "NAME", 
        "CALORIES", 
        "FAT", 
        "ACIDS", 
        "CARBOHYDRATES", 
        "SUGAR", 
        "PROTEIN", 
        "SALT", 
        "PRICE", 
        "PICTUREID", 
        "TYPE"
    ]
    rakstītājs.writerow(galvene)

    # Initialize page URL and page counter
    pašreizējās_lapas_url = f"{bāzes_url}/e-veikals/lv/produkti/saldumi-un-uzkodas/c/SH-13"
    pašreizējās_lapas_numurs = 1

    while True:
        print(f"Apstrādā lapu {pašreizējās_lapas_numurs}...")

        # Get the current page's product list content
        lapa = requests.get(pašreizējās_lapas_url, headers=GALVENES)
        zupa = BeautifulSoup(lapa.content, "html.parser")

        # Find all product links on the page
        saites = zupa.find_all('a', 'card__url js-gtm-eec-product-click')
        produktu_url = [bāzes_url + saite.get('href') for saite in saites]

        # Process each product URL to gather information
        for url in produktu_url:
            pārlūks.get(url)

            # Wait for the product page to fully load
            try:
                WebDriverWait(pārlūks, 30).until(
                    EC.presence_of_element_located((By.ID, 'product_tabs_vue_root'))
                )
            except TimeoutException:
                continue  # Skip to the next product if this one fails to load

            # Get page source and parse with BeautifulSoup
            html = pārlūks.page_source
            produkta_zupa = BeautifulSoup(html, "html.parser")

            # -----------------------
            #    EXTRACT DATA
            # -----------------------

            # Get product name
            try:
                produkta_nosaukums = produkta_zupa.find('h1', class_='name').text.strip()
            except AttributeError:
                produkta_nosaukums = "N/A"

            # Get image URL
            try:
                attēls = produkta_zupa.find('div', {'class': 'product__main-image'})
                attēla_url = attēls.find('img')['data-src']
            except (AttributeError, TypeError):
                attēla_url = "N/A"

            # Get price
            try:
                cena_raw = produkta_zupa.find('p', {'class': 'price-per'}).text.strip()
                skaitļi = re.findall(r'\d+', cena_raw)
                if len(skaitļi) >= 2:
                    cena = float(f"{skaitļi[0]}.{skaitļi[1]}")
                elif len(skaitļi) == 1:
                    cena = float(skaitļi[0])
                else:
                    cena = 0.0
            except AttributeError:
                cena = "N/A"

            # Get and process nutritional values, ensuring only numerical values
            uzturvērtības = []
            uzturvērtību_elementi = produkta_zupa.find('th', string='Uzturvērtība')
            if uzturvērtību_elementi:
                tabula = uzturvērtību_elementi.find_parent('table')
                rindiņas = tabula.find_all('tr')[1:]  # Skip header row
                for rinda in rindiņas:
                    kolonnas = rinda.find_all('td')
                    if len(kolonnas) == 2:
                        uzturvērtība = kolonnas[1].text.strip()
                        # Extract only numerical values
                        skaitliskā_vērtība = re.search(r'[\d,.]+', uzturvērtība)
                        uzturvērtības.append(skaitliskā_vērtība.group() if skaitliskā_vērtība else "N/A")

            # Ensure exactly MAX_UZTURVĒRTĪBU_SK items in the nutrition list,
            # filling missing ones with "N/A"
            while len(uzturvērtības) < MAX_UZTURVĒRTĪBU_SK:
                uzturvērtības.append("N/A")

            # Convert 'uzturvērtības[0]' from kJ to kcal by dividing by 4.184
            if uzturvērtības[0] != "N/A":
                try:
                    energy_kj = float(uzturvērtības[0].replace(',', '.'))
                    energy_kcal = energy_kj / 4.184
                    uzturvērtības[0] = f"{energy_kcal:.1f}"
                except ValueError:
                    uzturvērtības[0] = "N/A"

            # -----------------------
            #  PREPARE & WRITE DATA
            # -----------------------
            detaļas = [
                produkta_nosaukums,   # NAME
                uzturvērtības[0],     # CALORIES
                uzturvērtības[1],     # FAT
                uzturvērtības[2],     # ACIDS
                uzturvērtības[3],     # CARBOHYDRATES
                uzturvērtības[4],     # SUGAR
                uzturvērtības[5],     # PROTEIN
                uzturvērtības[6],     # SALT
                str(cena),            # PRICE
                attēla_url,           # PICTUREID
                "Saldumi"                # TYPE
            ]

            # Optionally skip rows with any "N/A" values
            if "N/A" not in detaļas:
                rakstītājs.writerow(detaļas)

        # ------------------------------------
        #  FIND NEXT PAGE (via data-page logic)
        # ------------------------------------
        nākamās_lapas_saite = None
        # The pagination block typically has links like:
        # <li class="pagination__item"><a href="..." data-page="2">2</a></li>
        paginācijas_saites = zupa.select('li.pagination__item a')

        for saite in paginācijas_saites:
            if saite.has_attr('data-page'):
                next_page_num = int(saite['data-page'])
                # If it's bigger than our current page, we assume it's the next
                if next_page_num > pašreizējās_lapas_numurs:
                    nākamās_lapas_saite = saite['href']
                    pašreizējās_lapas_numurs = next_page_num
                    break

        if nākamās_lapas_saite:
            # Build absolute URL if needed
            if nākamās_lapas_saite.startswith('/'):
                pašreizējās_lapas_url = bāzes_url + nākamās_lapas_saite
            else:
                pašreizējās_lapas_url = nākamās_lapas_saite

            print(f"---> Pāriet uz lapu numur {pašreizējās_lapas_numurs}: {pašreizējās_lapas_url}")
            # Optional small delay so we don't overload the server
            time.sleep(1)
        else:
            print("Vairs nav lapu vai nav atrasts lielāks 'data-page'. Apstrāde pabeigta!")
            break

# Close WebDriver
pārlūks.quit()
