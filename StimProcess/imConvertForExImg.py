"""Convert the images to the right sizes.

This converts the size of the images in "OriginalStims," and saves them
elsewhere.
"""

from PIL import Image
import os
import glob


def resizeIt(fl, saveDir):
    imHeight = 80.0

    print(fl)
    im = Image.open(f)
    imH, imW = im.size
    imNewW = int(imHeight / imH * imW)
    imNewH = int(imHeight)

    im = im.resize((imNewH, imNewW), resample=Image.LANCZOS)
    flname = f.split('/')[-1]
    im.save(os.path.join(saveDir, flname))


bf = glob.glob('./BF/*.png')
wf = glob.glob('./WF/*.png')
df = glob.glob('./Dems/*.png')
rf = glob.glob('./Reps/*.png')

for f in bf + wf:
    resizeIt(f, "RaceExamples")

for f in df + rf:
    resizeIt(f, "PolExamples")
