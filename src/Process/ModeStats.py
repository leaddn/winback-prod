#!/usr/bin/env python3

import pandas as pd
import numpy as np
import sys
import os
import regex as re
import matplotlib.pyplot as plt
from os.path import exists
from mysql.connector import Error

import plotly.figure_factory as ff
import plotly.express as px
import plotly.graph_objects as gox

from tqdm import tqdm
from time import sleep

class ModeStats:
    def getPattern(self, pattern, string):
        contact = "\{1,\d{1,3},\d{1,3},\d{1,3}\}\{\d,\d{1,3},\d{1,3},\d{1,3}\};|\{1,\d{1,3},\d{1,3},\d{1,3}\}\{1,\d{1,3},\d{1,3},\d{1,3}\};|\{\d,\d{1,3},\d{1,3},\d{1,3}\}\{1,\d{1,3},\d{1,3},\d{1,3}\};"
        if re.findall(contact, string):
            return len(re.findall(pattern, string))

    def getPatternSearch(self, doc, pattern_a, pattern_b, filename, filesize, col, contact):
        countOccurences = 0
        pattern_c = "\{1,\d{1,3},\d{1,3},\d{1,3}\}\{\d,\d{1,3},\d{1,3},\d{1,3}\};|\{1,\d{1,3},\d{1,3},\d{1,3}\}\{1,\d{1,3},\d{1,3},\d{1,3}\};|\{\d,\d{1,3},\d{1,3},\d{1,3}\}\{1,\d{1,3},\d{1,3},\d{1,3}\};"
        for line in doc:
            if "New parameters" in line or "Treatment start" in line:
                if pattern_b != "":
                    if re.search(pattern_a, line) and re.search(pattern_b, line) and re.search(pattern_c, line):
                        countOccurences += 1
                else:
                    if contact == True:
                        if re.search(pattern_a, line) and re.search(pattern_c, line):
                            countOccurences += 1
                    else:
                        if re.search(pattern_a, line):
                            countOccurences += 1
        if countOccurences > 1:
            return {'device':filename, col:countOccurences, 'filesize':filesize}
            
                    
    def getTotal(self, df, col, file_list):
        mean = df[col].mean()
        percent = round(len(df)/len(file_list)*100)
        #print(percent)
        return {'mean':mean, 'percent':percent}


    def getResult(self, path):
        file_list = os.listdir(path)
        result_contact = []
        result_retCet = []
        result_retHiems = []
        result_mixHitens = []
        result_swap = []

        for file, i in zip(file_list, tqdm(range(len(file_list)-1))):
            file_stats = os.stat(path+file)
            filesize_mb = str(round(file_stats.st_size / (1024 * 1024), 2))
            file = file[:-4]
            if 'TEST' in file or 'DEMO' in file or 'LEA' in file:
                pass
            else:
                
                with open(path+file+".txt", encoding="utf8", errors='ignore') as f:
                    doc = f.readlines()
                    space = 20-len(file)
                    file += " "*space
                    swap_array = self.getPatternSearch(doc, "SWAP", "", file, filesize_mb, 'swap', True) #swap
                    if swap_array:
                        result_swap.append(swap_array)
                    contact_pattern = "\{1,\d{1,3},\d{1,3},\d{1,3}\}\{1,\d{1,3},\d{1,3},\d{1,3}\};"
                    contact_array = self.getPatternSearch(doc, contact_pattern, "", file, filesize_mb, 'contact', False) #contact
                    if contact_array:
                        result_contact.append(contact_array)
                    retCet_array = self.getPatternSearch(doc, "R:", "C:", file, filesize_mb, 'ret_cet', True) #ret/cet
                    if retCet_array:
                        result_retCet.append(retCet_array)
                    retHiems_array = self.getPatternSearch(doc, "R:", "S:", file, filesize_mb, 'ret_hiems', True) #ret/cet
                    if retHiems_array:
                        result_retHiems.append(retHiems_array)
                    mixHitens_array = self.getPatternSearch(doc, "M:", "RS:", file, filesize_mb, 'mix_hitens', True) #ret/cet
                    if mixHitens_array:
                        result_mixHitens.append(mixHitens_array)

        return result_contact, result_retCet, result_retHiems, result_mixHitens, result_swap

    def main(self):
        args = sys.argv[1:]
        path = args[0]
        result_contact, result_retCet, result_retHiems, result_mixHitens, result_swap = self.getResult(path)
        file_list = os.listdir(path)

        if result_mixHitens != []:
            df_mixHitens = pd.DataFrame(result_mixHitens)
            total_mixHitens = self.getTotal(df_mixHitens, 'mix_hitens', file_list)
            with pd.ExcelWriter('./src/Process/output/'+"modeStats_.xlsx") as writer:
                df_mixHitens.to_excel(writer, sheet_name='mixHitens')
        
        df_swap = pd.DataFrame(result_swap)
        df_contact = pd.DataFrame(result_contact)
        df_retCet = pd.DataFrame(result_retCet)
        df_retHiems = pd.DataFrame(result_retHiems)
        
        
        total_swap = self.getTotal(df_swap, 'swap', file_list)

        total_contact = self.getTotal(df_contact, 'contact', file_list)

        total_retCet = self.getTotal(df_retCet, 'ret_cet', file_list)

        total_retHiems = self.getTotal(df_retHiems, 'ret_hiems', file_list)



        #total_array = {'swap':total_swap, 'contact':total_contact, 'ret_cet':total_retCet, 'ret_hiems':total_retHiems, 'mix_hitens':total_mixHitens}
        #df_result = pd.DataFrame(total_array)
        
        with pd.ExcelWriter('./src/Process/output/'+"modeStats_.xlsx") as writer:
            #df_result.to_excel(writer, sheet_name='result')
            df_swap.to_excel(writer, sheet_name='swap')
            df_contact.to_excel(writer, sheet_name='contact')
            df_retCet.to_excel(writer, sheet_name='retCet')
            df_retHiems.to_excel(writer, sheet_name='retHiems')
            #df_mixHitens.to_excel(writer, sheet_name='mixHitens')

if __name__ == "__main__":
    modeStats = ModeStats()
    modeStats.main()